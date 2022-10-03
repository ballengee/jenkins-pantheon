<?php

namespace Wpo\Services;

use \Wpo\Core\Url_Helpers;

use \Wpo\Services\Authentication_Service;
use \Wpo\Services\Error_Service;
use \Wpo\Services\Id_Token_Service;
use \Wpo\Services\Log_Service;
use \Wpo\Services\Options_Service;
use \Wpo\Services\Request_Service;

use \Wpo\Tests\Self_Test;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Services\Router_Service')) {

    class Router_Service
    {

        public static function has_route()
        {
            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);

            if (
                (!empty($_POST['id_token']) && !empty($_POST['state']))
                || (!empty($_POST['code']) && !empty($_POST['state']))
                || (!empty($_POST['error']) && !empty($_POST['state']))
            ) {
                $state = self::process_state_url($_POST['state'], $request);
                $request->set_item('state', $state);

                if (Options_Service::get_global_boolean_var('use_pkce') && class_exists('\Wpo\Services\Pkce_Service')) {
                    $state = \Wpo\Services\Pkce_Service::process_state_with_verifier();
                    $request->set_item('state', $state);
                }

                unset($_POST['state']);

                if (!empty($_POST['id_token'])) {
                    $request->set_item('encoded_id_token', sanitize_text_field($_POST['id_token']));
                    unset($_POST['id_token']);
                }

                if (!empty($_POST['code'])) {
                    $request->set_item('code', sanitize_text_field($_POST['code']));
                    unset($_POST['code']);
                }
            }

            if (!empty($_POST['RelayState'])) {
                $relay_state = self::process_state_url($_POST['RelayState'], $request);
                $request->set_item('relay_state', $relay_state); // -> Cannot be unset because there dependies relying on it
            }

            if (isset($_POST['error'])) {
                $error_string = sanitize_text_field($_POST['error']) . (isset($_POST['error_description']) ? \sanitize_text_field($_POST['error_description']) : '');
                Log_Service::write_log('ERROR', __METHOD__ . ' -> ' . $error_string);
            }

            // check for user sync start request via external link
            if (!empty($_REQUEST['wpo365_sync_run']) && $_REQUEST['wpo365_sync_run'] == 'start') {

                if (class_exists('\Wpo\Sync\SyncV2_Service') && !empty($_REQUEST['job_id'])) {
                    \Wpo\Sync\SyncV2_Service::sync_users(sanitize_text_field($_REQUEST['job_id']));
                    return true;
                }
            }

            if ($request->get_item('mode') == 'mailAuthorize') {
                add_action('init', '\Wpo\Services\Router_Service::route_mail_authorize');
                return true;
            }

            if ($request->get_item('mode') == 'selfTest') {
                add_action('init', '\Wpo\Services\Router_Service::route_plugin_selftest');
                return true;
            }

            // Check if SSO is enabled
            if (Options_Service::get_global_boolean_var('no_sso', false)) {
                return false;
            }

            // initiate openidconnect / saml flow
            if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'openidredirect') {
                add_action('init', '\Wpo\Services\Router_Service::route_initiate_user_authentication');
                return true;
            }

            // process openid connect error
            if (isset($_POST['error'])) {
                add_action('init', '\Wpo\Services\Router_Service::route_openidconnect_error');
                return true;
            }

            // process openid connect id token (hybrid flow)
            if (!empty($request->get_item('state'))) {

                if (!empty($request->get_item('encoded_id_token'))) {
                    add_action('init', '\Wpo\Services\Router_Service::route_openidconnect_token');
                    return true;
                }

                if (!empty($request->get_item('code'))) {
                    add_action('init', '\Wpo\Services\Router_Service::route_openidconnect_code');
                    return true;
                }
            }

            // process saml response
            if (!empty($_REQUEST['SAMLResponse'])) {
                add_action('init', '\Wpo\Services\Router_Service::route_saml2_response');
                return true;
            }

            return false;
        }

        /**
         * Route to initialize user authentication with the option to do
         * so with OpenID Connect or with SAML.
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_initiate_user_authentication()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (Options_Service::get_global_boolean_var('use_saml')) {
                self::route_saml2_initiate();
            } else {
                self::route_openidconnect_initiate();
            }
        }

        /**
         * Route to redirect user to login.microsoftonline.com
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_openidconnect_initiate()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $login_hint = !empty($_REQUEST['login_hint'])
                ? $_REQUEST['login_hint']
                : null;

            $redirect_to = !empty($_POST['redirect_to'])
                ? esc_url_raw($_POST['redirect_to'])
                : null;

            if (Options_Service::is_wpo365_configured()) {

                if (Options_Service::get_global_boolean_var('use_b2c') &&  \class_exists('\Wpo\Services\Id_Token_Service_B2c')) {
                    $authUrl = \Wpo\Services\Id_Token_Service_B2c::get_openidconnect_url($login_hint, $redirect_to);
                } else {
                    $authUrl = Id_Token_Service::get_openidconnect_url($login_hint, $redirect_to);
                }

                Url_Helpers::force_redirect($authUrl);
                exit();
            }
        }

        /**
         * Route to redirect user to the configured SAML 2.0 IdP
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_saml2_initiate()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $redirect_to = !empty($_POST['redirect_to'])
                ? esc_url_raw($_POST['redirect_to'])
                : null;

            $params = array();

            if (!empty($_POST['domain_hint'])) {
                $params['whr'] = sanitize_text_field(\strtolower(\trim($_POST['domain_hint'])));
            }

            if (Options_Service::is_wpo365_configured()) {
                \Wpo\Services\Saml2_Service::initiate_request($redirect_to, $params);
                exit();
            }
        }

        /**
         * Route to redirect user to the configured SAML 2.0 IdP
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_saml2_response()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (Options_Service::is_wpo365_configured()) {
                try {
                    $wpo_usr = Authentication_Service::authenticate_saml2_user();
                    Url_Helpers::goto_after($wpo_usr);
                } catch (\Exception $e) {
                    Log_Service::write_log('ERROR', __METHOD__ . ' -> Could not process SAML 2.0 response (' . $e->getMessage() . ')');
                    Authentication_Service::goodbye(Error_Service::SAML2_ERROR);
                    exit();
                }
            }
        }

        /**
         * Route to process an incoming id token
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_openidconnect_token()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (Options_Service::get_global_boolean_var('use_id_token_parser_v2') && \class_exists('\Wpo\Services\Id_Token_Service_Deprecated')) {
                \Wpo\Services\Id_Token_Service_Deprecated::process_openidconnect_token();
            } else {
                Id_Token_Service::process_openidconnect_token();
            }

            $wpo_usr = Authentication_Service::authenticate_oidc_user();
            Url_Helpers::goto_after($wpo_usr);
        }

        /**
         * Route to process an incoming authorization code
         * 
         * @since 18.0
         * 
         * @return void
         */
        public static function route_openidconnect_code()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (strcasecmp(Options_Service::get_global_string_var('oidc_flow'), 'code') === 0) {
                if (Options_Service::get_global_boolean_var('use_b2c')) {
                    \Wpo\Services\Id_Token_Service_B2c::process_openidconnect_code();
                } else {
                    \Wpo\Services\Id_Token_Service::process_openidconnect_code();
                }
            } else {
                Log_Service::write_log('ERROR', __METHOD__ . ' -> An authorization code was received but support for the "authorization code flow" has not been configured.');
                Authentication_Service::goodbye(Error_Service::CHECK_LOG);
                exit();
            }

            $wpo_usr = Authentication_Service::authenticate_oidc_user();
            Url_Helpers::goto_after($wpo_usr);
        }

        /**
         * Route to sign user out of WordPress and redirect to login page
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_openidconnect_error()
        {
            Authentication_Service::goodbye(Error_Service::CHECK_LOG);
            exit();
        }

        /**
         * Route to execute plugin selftest and then redirect user back to results (or landing page)
         * 
         * @since 11.0
         * 
         * @return void
         */
        public static function route_plugin_selftest()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            // Perform a self test
            new Self_Test();

            // Get redirect target (default / wpo365 not configured)
            $redirect_to = !empty($_REQUEST['redirect_to'])
                ? esc_url_raw($_REQUEST['redirect_to'])
                : $GLOBALS['WPO_CONFIG']['url_info']['wp_site_url'];

            // Try update redirect target (wpo365 configured)
            $redirect_url = Url_Helpers::get_redirect_url($redirect_to);
            $redirect_url = str_replace('flushPermaLinks=true', '', $redirect_url);

            Url_Helpers::force_redirect($redirect_url);
        }

        /**
         * Route to execute mail authorization with delegated permissions.
         * 
         * @since 19.0
         * 
         * @return void
         */
        public static function route_mail_authorize()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            // Process the incoming authorization code and request an ID token, access and refresh token.
            \Wpo\Services\Id_Token_Service::process_openidconnect_code('https://graph.microsoft.com/Mail.Send', false);

            // Uses the tokens received to create a mail user object
            $authorization_result = \Wpo\Mail\Mail_Authorization_Helpers::authorize_mail_user();

            if (is_wp_error($authorization_result)) {
                Log_Service::write_log('ERROR', sprintf('%s -> Mail authorization failed. Please check log for previous errors.', __METHOD__));
            }

            // Get redirect target (default / wpo365 not configured)
            $redirect_to = !empty($_REQUEST['redirect_to'])
                ? esc_url_raw($_REQUEST['redirect_to'])
                : $GLOBALS['WPO_CONFIG']['url_info']['wp_site_url'];

            // Try update redirect target (wpo365 configured)
            $redirect_url = Url_Helpers::get_redirect_url($redirect_to);

            Url_Helpers::force_redirect($redirect_url);
        }

        /**
         * Analyzes the status URL.
         * 
         * @since 19.0
         * 
         * @param mixed $url 
         * @param mixed $request 
         * @return string 
         */
        private static function process_state_url($url, $request)
        {
            $url = urldecode($url);
            $url = wp_sanitize_redirect($url);

            if (false !== stripos($url, 'mode=mailAuthorize')) {
                $url = str_replace('mode=mailAuthorize&', '', $url);
                $request->set_item('mode', 'mailAuthorize');
            }

            if (false !== stripos($url, 'mode=selfTest')) {
                $url = str_replace('mode=selfTest&', '', $url);
                $request->set_item('mode', 'selfTest');
            }

            // If state is a relative URL then try to fix it.
            if (strpos($url, '/') === 0) {
                $url = sprintf(
                    '%s%s',
                    rtrim($GLOBALS['WPO_CONFIG']['url_info']['wp_site_url'], '/'),
                    $url
                );
            }

            return $url;
        }
    }
}
