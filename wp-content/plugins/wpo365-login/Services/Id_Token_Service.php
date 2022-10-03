<?php

namespace Wpo\Services;

use \Wpo\Services\Authentication_Service;
use \Wpo\Services\Error_Service;
use \Wpo\Services\Jwt_Token_Service;
use \Wpo\Services\Log_Service;
use \Wpo\Services\Options_Service;
use \Wpo\Services\Request_Service;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Services\Id_Token_Service')) {

    class Id_Token_Service
    {

        /**
         * Constructs the oauth authorize URL that is the end point where the user will be sent for authorization.
         * 
         * @since 4.0
         * 
         * @since 11.0 Dropped support for the v1 endpoint.
         * 
         * @param $login_hint string Login hint that will be added to Open Connect ID link
         * @param $redirect_to string Link where the user will be redirected to
         * 
         * @return string if everthing is configured OK a valid authorization URL
         */
        public static function get_openidconnect_url($login_hint = null, $redirect_to = null)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $application_id = Options_Service::get_aad_option('application_id');
            $directory_id = Options_Service::get_aad_option('tenant_id');
            $oidc_flow = Options_Service::get_global_string_var('oidc_flow');
            $redirect_url = Options_Service::get_aad_option('redirect_url');
            $multi_tenanted = Options_Service::get_global_boolean_var('multi_tenanted') && !Options_Service::get_global_boolean_var('use_b2c');

            $redirect_to = !empty($redirect_to)
                ? $redirect_to
                : (
                    (isset($_SERVER['HTTP_REFERER'])
                        ? $_SERVER['HTTP_REFERER']
                        : $GLOBALS['WPO_CONFIG']['url_info']['wp_site_url'])
                );

            /**
             * @since   16.0    Filters the redirect_to url
             */
            $redirect_to = apply_filters('wpo365/cookie/remove/url', $redirect_to);
            $redirect_to = urlencode($redirect_to);

            $params = array(
                'client_id'             => $application_id,
                'redirect_uri'          => $redirect_url,
                'response_mode'         => 'form_post',
                'scope'                 => 'openid email profile',
                'state'                 => $redirect_to,
                'nonce'                 => wp_create_nonce('oidc'),
            );

            $params['response_type'] = $oidc_flow == 'code' ? 'code' : 'id_token code';

            // Add Proof Key for Code Exchange challenge if required
            if (Options_Service::get_global_boolean_var('use_pkce') && class_exists('\Wpo\Services\Pkce_Service')) {
                \Wpo\Services\Pkce_Service::add_and_memoize_verifier($params);
            }

            /**
             * @since 9.4
             * 
             * Add ability to configure a domain hint to prevent Microsoft from
             * signing in users that are already logged in to a different O365 tenant.
             */
            $domain_hint = Options_Service::get_global_string_var('domain_hint');

            if (!empty($domain_hint)) {
                $params['domain_hint'] = $domain_hint;
            }

            if (!empty($login_hint)) {
                $params['login_hint'] = $login_hint;
            }

            if (true === Options_Service::get_global_boolean_var('add_select_account_prompt')) {
                $params['prompt'] = 'select_account';
            } else if (true === Options_Service::get_global_boolean_var('add_create_account_prompt')) {
                $params['prompt'] = 'create';
            }

            if (true === $multi_tenanted) {
                $directory_id = 'common';
                $multi_tenanted_api_permissions = Options_Service::get_global_list_var('multi_tenanted_api_permissions');

                foreach ($multi_tenanted_api_permissions as $key => $permission) {

                    $permission = strtolower(trim($permission));

                    if (!empty($permission)) {
                        $params['scope'] = $params['scope'] . " $permission";
                    }
                }
            }

            $auth_url = 'https://login.microsoftonline.com/'
                . $directory_id
                . '/oauth2'
                . '/v2.0'
                . '/authorize?'
                . http_build_query($params, '', '&');

            Log_Service::write_log('DEBUG', __METHOD__ . " -> Open ID Connect URL: $auth_url");

            return $auth_url;
        }

        /**
         * Processes the ID token and caches it for the current request. If an authorization code is sent
         * along, it will be saved so the it can be used when requesting access tokens for the current
         * user (if integration is configured).
         * 
         * @return  void
         */
        public static function process_openidconnect_token($force_goodbye = true)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            // Decode the id_token
            $id_token = self::decode_id_token();

            // Handle if token could not be processed
            if ($id_token === false) {

                if (true === $force_goodbye) {
                    Log_Service::write_log('ERROR', __METHOD__ . ' -> ID token could not be processed and user will be redirected to default Wordpress login.');
                    Authentication_Service::goodbye(Error_Service::ID_TOKEN_ERROR);
                    exit();
                }

                return;
            }

            // Handle if nonce is invalid 
            if (!Options_Service::get_global_boolean_var('skip_nonce_verification')) {

                if (!wp_verify_nonce($id_token->nonce, 'oidc')) {
                    Log_Service::write_log('WARN', __METHOD__ . ' -> Could not successfully validate oidc nonce with value ' . $id_token->nonce);
                }
            }

            // Log id token if configured
            if (true === Options_Service::get_global_boolean_var('debug_log_id_token')) {
                Log_Service::write_log('DEBUG', $id_token);
            }

            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
            $request->set_item('id_token', $id_token);
        }

        /**
         * Helper to process the authorization code which is then used to request an ID and access token.
         * 
         * @since   18.0
         * 
         * @return void 
         */
        public static function process_openidconnect_code($scope = '', $force_goodbye = true)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);

            $code = $request->get_item('code');
            $mode = $request->get_item('mode');

            if (empty($code)) {
                Log_Service::write_log('ERROR', sprintf('%s -> Authorization code not found', __METHOD__));
                return;
            }

            $use_mail_config = $mode == 'mailAuthorize';

            $directory_id = $use_mail_config ? Options_Service::get_aad_option('mail_tenant_id') : Options_Service::get_aad_option('tenant_id');
            $application_id = $use_mail_config ? Options_Service::get_aad_option('mail_application_id') : Options_Service::get_aad_option('application_id');
            $application_secret = $use_mail_config ? Options_Service::get_aad_option('mail_application_secret') : Options_Service::get_aad_option('application_secret');
            $redirect_url = $use_mail_config ? Options_Service::get_aad_option('mail_redirect_url') : Options_Service::get_aad_option('redirect_url');
            $multi_tenanted = Options_Service::get_global_boolean_var('multi_tenanted') && !Options_Service::get_global_boolean_var('use_b2c');

            if (true === $multi_tenanted) {
                $directory_id = 'common';
            }

            $params = array(
                'client_id'     => $application_id,
                'response_type' => 'token',
                'redirect_uri'  => $redirect_url,
                'response_mode' => 'form_post',
                'grant_type'    => 'authorization_code',
                'scope'         => 'openid email profile offline_access' . (empty($scope) ? '' : (' ' . $scope)),
                'code'          => $code,
                'client_secret' => $application_secret,
            );

            if (Options_Service::get_global_boolean_var('use_pkce') && class_exists('\Wpo\Services\Pkce_Service')) {
                $pkce_code_verifier = \Wpo\Services\Pkce_Service::get_personal_pkce_code_verifier();

                if (!empty($pkce_code_verifier)) {
                    $params['code_verifier'] = $pkce_code_verifier;
                } else {
                    $warning = 'Cannot retrieve an (ID) token because the Administrator 
                        has configured the use of a Proof Key for Code Exchange but a code verifier for the current
                        user cannot be found. See the <a href="https://docs.wpo365.com/article/149-require-proof-key-for-code-exchange-pkce" target="_blank">online documentation</a> 
                        for detailed step-by-step instructions on how to configure the WPO365 | LOGIN plugin to use a Proof Key for Code Exchange.';
                    Log_Service::write_log('ERROR', __METHOD__ . " -> $warning");

                    $access_token_errors = $request->get_item('access_token_errors') ?: array();
                    $access_token_errors[] = $warning;
                    $request->set_item('access_token_errors', $access_token_errors);

                    return;
                }
            }

            $skip_ssl_verify = !Options_Service::get_global_boolean_var('skip_host_verification');

            $token_url = 'https://login.microsoftonline.com/'
                . $directory_id
                . '/oauth2'
                . '/v2.0'
                . '/token';

            $response = wp_remote_post($token_url, array(
                'blocking' => true,
                'sslverify' => $skip_ssl_verify,
                'timeout' => 30, // timeout in seconds
                'body' => $params,
            ));

            if (is_wp_error($response)) {
                Log_Service::write_log('ERROR', sprintf('%s -> Error occured whilst fetching from %s: %s', __METHOD__, $token_url, $response->get_error_message()));
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response));

            if (empty($body)) {
                Log_Service::write_log('ERROR', sprintf('%s -> Error occured whilst fetching from %s: See next line for details.', __METHOD__, $token_url));
                Log_Service::write_log('ERROR', $response);
                return;
            }

            if (property_exists($body, 'error')) {
                $message = property_exists($body, 'error_description') ? $body->error_description : $body->error;
                Log_Service::write_log('ERROR', sprintf('%s -> Error occured whilst fetching from %s: %s', __METHOD__, $token_url, $message));
                return;
            }

            if (property_exists($body, 'access_token')) {
                $access_token = new \stdClass();
                $access_token->access_token = $body->access_token;

                if (property_exists($body, 'expires_in')) {
                    $access_token->expiry = time() + intval($body->expires_in);
                }

                if (property_exists($body, 'scope')) {
                    $access_token->scope = $body->scope;
                }

                $access_tokens = $request->get_item('access_tokens');

                if (empty($access_tokens)) {
                    $access_tokens = array();
                }

                // Save access token as request variable -> will be saved on shutdown
                $access_tokens[] = $access_token;
                $request->set_item('access_tokens', $access_tokens);
            }

            if (property_exists($body, 'refresh_token')) {
                $refresh_token = new \stdClass();
                $refresh_token->refresh_token = $body->refresh_token;

                if (property_exists($body, 'scope')) {
                    $refresh_token->scope = $body->scope;
                }

                $request->set_item('refresh_token', $refresh_token);
            }

            if (property_exists($body, 'id_token')) {
                $request->set_item('encoded_id_token', $body->id_token);
                self::process_openidconnect_token($force_goodbye);
                return;
            }

            Log_Service::write_log('ERROR', sprintf('%s -> ID token not found in data retrieved from token endpoint [see next line for response body]', __METHOD__));
            Log_Service::write_log('DEBUG', $body);
        }

        /**
         * Unraffles the incoming JWT id_token with the help of Firebase\JWT and the tenant specific public keys available from Microsoft.
         * 
         * @since   1.0
         *
         * @return  object|boolean 
         */
        public static function decode_id_token()
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
            $id_token = $request->get_item('encoded_id_token');

            // Get the token and get it's header for a first analysis
            if (empty($id_token)) {
                Log_Service::write_log('ERROR', __METHOD__ . ' -> ID token not found in posted data.');
                return false;
            }

            $claims = Jwt_Token_Service::validate_signature($id_token);

            if (is_wp_error($claims)) {
                Log_Service::write_log('ERROR', $claims->get_error_message());
                return false;
            }

            return $claims;
        }
    }
}
