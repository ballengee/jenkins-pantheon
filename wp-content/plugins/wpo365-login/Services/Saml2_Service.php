<?php

namespace Wpo\Services;

use \Wpo\Core\Wpmu_Helpers;
use \Wpo\Services\Authentication_Service;
use \Wpo\Services\Error_Service;
use \Wpo\Services\Log_Service;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Services\Saml2_Service')) {

    class Saml2_Service
    {

        /**
         * Iniates a SAML 2.0 request and redirects the user to the IdP.
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function initiate_request($redirect_to, $params = array())
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            /**
             * @since   16.0    Filters the redirect_to url
             */

            $redirect_to = apply_filters('wpo365/cookie/remove/url', $redirect_to);
            $redirect_to = urlencode($redirect_to);

            require_once($GLOBALS['WPO_CONFIG']['plugin_dir'] . '/OneLogin/_toolkit_loader.php');

            $forceAuthn = Options_Service::get_global_boolean_var('saml_force_authn');
            $saml_settings = self::saml_settings();
            $auth = new \OneLogin_Saml2_Auth($saml_settings);
            $auth->login($redirect_to, $params, $forceAuthn);
        }

        /**
         * Gets an attribute / claim from the SAML 2.0 response.
         * 
         * @since   11.0
         * 
         * @param   $name               string  WPO365 User field name (looked up in the claim mappings setting)
         * @param   $saml_attributes    array   Attributes received as part of the SAML response
         * @param   $to_lower           boolean True if the attribute value returned should be converted to lower case
         * 
         * @return  string  Attribute's value as string               
         */
        public static function get_attribute($name, $saml_attributes, $to_lower = false)
        {

            // TODO Get mappings from configuration
            $claim_mappings = array(
                'preferred_username' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
                'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
                'first_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
                'last_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
                'full_name' => 'http://schemas.microsoft.com/identity/claims/displayname',
                'tid' => 'http://schemas.microsoft.com/identity/claims/tenantid',
                'objectidentifier' => 'http://schemas.microsoft.com/identity/claims/objectidentifier',
            );

            if (isset($saml_attributes[$claim_mappings[$name]]) && is_array($saml_attributes[$claim_mappings[$name]]) && sizeof($saml_attributes[$claim_mappings[$name]]) > 0) {
                return $to_lower
                    ? \strtolower(htmlentities($saml_attributes[$claim_mappings[$name]][0]))
                    : htmlentities($saml_attributes[$claim_mappings[$name]][0]);
            }

            return '';
        }

        /**
         * Creates a OneLogin settings object with the settings configured through the WPO365 wizard.
         * 
         * @since   11.0
         * 
         * @return  mixed(array|boolean)   Array with OneLogin (non-advanced) settings or true / false when validating.
         */
        public static function saml_settings($validate = false)
        {

            $base_url = Options_Service::get_global_string_var('saml_base_url');
            $sp_entity_id = Options_Service::get_global_string_var('saml_sp_entity_id');
            $sp_acs_url = Options_Service::get_global_string_var('saml_sp_acs_url');
            $sp_sls_url = Options_Service::get_global_string_var('saml_sp_sls_url');
            $idp_entity_id = Options_Service::get_global_string_var('saml_idp_entity_id');
            $idp_ssos_url = Options_Service::get_global_string_var('saml_idp_ssos_url');
            $idp_sls_url = Options_Service::get_global_string_var('saml_idp_sls_url');
            $x509cert = Options_Service::get_aad_option('saml_x509_cert');

            $log_level = $validate ? 'WARN' : 'ERROR';
            $has_errors = false;

            $exit_on_error = function () use ($validate) {

                if (!$validate) {
                    Authentication_Service::goodbye(Error_Service::SAML2_ERROR);
                    exit();
                }
            };

            if (empty($base_url)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Base URL cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($sp_entity_id)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Service Provider Entity ID cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($sp_acs_url)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Service Provider Assertion Consumer Service URL cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($sp_sls_url)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Service Provider Single Logout Service URL cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($idp_entity_id)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Identity Provider Entity ID cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($idp_ssos_url)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Identity Provider Single Sign-on Service URL cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($idp_sls_url)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (Identity Provider Single Logout Service URL cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (empty($x509cert)) {
                Log_Service::write_log($log_level, __METHOD__ . ' -> SAML 2.0 error (X509 Certificate cannot be empty)');
                $exit_on_error();
                $has_errors = true;
            }

            if (true === $validate) {
                return !$has_errors;
            }

            $settings = array(
                'strict' => true,
                'debug' => false,
                'baseurl' => $base_url,
                'sp' => array(
                    'entityId' => $sp_entity_id,
                    'assertionConsumerService' => array(
                        'url' => $sp_acs_url,
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    ),
                    'singleLogoutService' => array(
                        'url' => $sp_sls_url,
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    ),
                    'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
                    'x509cert' => '',
                    'privateKey' => '',
                ),
                'idp' => array(
                    'entityId' => $idp_entity_id,
                    'singleSignOnService' => array(
                        'url' => $idp_ssos_url,
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    ),
                    'singleLogoutService' => array(
                        'url' => $idp_sls_url,
                        'responseUrl' => '',
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    ),
                    'x509cert' => $x509cert,
                ),
            );

            /**
             * @since 11.14
             * 
             * Example:
             * 
             * define( 'WPO_SAML2_ADVANCED_SETTINGS', 
             *  array( 
             *    'security' => array(
             *      'requestedAuthnContext' => array (
             *        'urn:federation:authentication:windows'
             *      )
             *    ) 
             *  )
             * );
             */

            if (defined('WPO_SAML2_ADVANCED_SETTINGS') && is_array(constant('WPO_SAML2_ADVANCED_SETTINGS'))) {
                return array_merge($settings, constant('WPO_SAML2_ADVANCED_SETTINGS'));
            }

            return $settings;
        }

        public static function check_message_id($message_id)
        {
            $cache = Wpmu_Helpers::mu_get_transient('wpo365_saml_message_ids');

            if (empty($cache) || !\is_array($cache)) {
                $cache = array(
                    'last_write_index' => 0,
                    'slots' => array(
                        0 => array($message_id),
                        1 => array(),
                        2 => array(),
                        3 => array(),
                        4 => array(),
                        5 => array(),
                    ),
                );
                return;
            }

            $minutes = intval(date('i'));
            $mod = $minutes % 10;
            $write_slot = $minutes - $mod / 10;

            foreach ($cache['slots'] as $slot) {
                $index = array_search($message_id, $slot);

                if (false !== $index) {
                    Log_Service::write_log('ERROR', __METHOD__ . ' -> SAML 2.0 error (replay attack detected: SAML message ID already used)');
                    Authentication_Service::goodbye(Error_Service::TAMPERED_WITH);
                    exit();
                }
            }

            $cache['slots'][$write_slot][] = $message_id;
            $cache['slots'][(($write_slot + 1) % 6)] = array();

            Wpmu_Helpers::mu_set_transient('wpo365_saml_message_ids', $cache);
        }
    }
}
