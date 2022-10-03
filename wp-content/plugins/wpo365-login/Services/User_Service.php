<?php

namespace Wpo\Services;

// Prevent public access to this script
defined('ABSPATH') or die();

use \Wpo\Core\Domain_Helpers;
use \Wpo\Core\User;
use \Wpo\Services\Authentication_Service;
use \Wpo\Services\Log_Service;
use \Wpo\Services\Options_Service;
use \Wpo\Services\Request_Service;
use \Wpo\Services\Saml2_Service;
use \Wpo\Services\User_Create_Service;

if (!class_exists('\Wpo\Services\User_Service')) {

    class User_Service
    {

        const USER_NOT_LOGGED_IN = 0;
        const IS_NOT_O365_USER = 1;
        const IS_O365_USER = 2;

        /**
         * Transform ID token in to internally used User represenation.
         * 
         * @since 7.17
         * 
         * @param $id_token string The open ID connect token received.
         * @return mixed(User|WP_Error) A new User object created from the id_token or WP_Error if the ID token could not be parsed
         */
        public static function user_from_id_token($id_token)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $preferred_username = isset($id_token->preferred_username)
                ? trim(strtolower($id_token->preferred_username))
                : '';

            $upn = isset($id_token->upn)
                ? trim(strtolower($id_token->upn))
                : '';

            $email = isset($id_token->email)
                ? trim(strtolower($id_token->email))
                : '';

            $first_name = isset($id_token->given_name)
                ? trim($id_token->given_name)
                : '';

            $last_name = isset($id_token->family_name)
                ? trim($id_token->family_name)
                : '';

            $full_name = isset($id_token->name)
                ? trim($id_token->name)
                : '';

            $tid = isset($id_token->tid)
                ? trim($id_token->tid)
                : '';

            $oid = isset($id_token->oid)
                ? trim($id_token->oid)
                : '';

            $groups = property_exists($id_token, 'groups') && is_array($id_token->groups)
                ? array_flip($id_token->groups)
                : array();

            $wpo_usr = new User();
            $wpo_usr->from_idp_token = true;
            $wpo_usr->first_name = $first_name;
            $wpo_usr->last_name = $last_name;
            $wpo_usr->full_name = $full_name;
            $wpo_usr->email = $email;
            $wpo_usr->preferred_username = $preferred_username;
            $wpo_usr->upn = $upn;
            $wpo_usr->name = $upn;
            $wpo_usr->tid = $tid;
            $wpo_usr->oid = $oid;
            $wpo_usr->groups = $groups;

            // Store for later e.g. custom (BuddyPress) fields
            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
            $request->set_item('wpo_usr', $wpo_usr);

            if (Options_Service::get_global_boolean_var('express_login')) {
                return $wpo_usr;
            }

            // Enrich -> Graph resource for user
            if (\class_exists('\Wpo\Services\User_Details_Service')) {
                $resource_identifier = !empty($wpo_usr->oid)
                    ? $wpo_usr->oid
                    : (
                        (!empty($wpo_usr->upn)
                            ? $wpo_usr->upn
                            : null)
                    );

                $graph_resource = \Wpo\Services\User_Details_Service::get_graph_user($resource_identifier);

                if (empty($wpo_usr->upn) && is_array($graph_resource) && array_key_exists('userPrincipalName', $graph_resource)) {
                    $wpo_usr->upn = $graph_resource['userPrincipalName'];
                }

                $wpo_usr->graph_resource = $graph_resource;

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            // Enrich -> Azure AD groups
            if (empty($wpo_usr->groups) && \class_exists('\Wpo\Services\User_Aad_Groups_Service') && \method_exists('\Wpo\Services\User_Aad_Groups_Service', 'get_aad_groups')) {
                \Wpo\Services\User_Aad_Groups_Service::get_aad_groups($wpo_usr);

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            // Improve quality of the data with graph resource
            if (\class_exists('\Wpo\Services\User_Details_Service') && \method_exists('\Wpo\Services\User_Details_Service', 'try_improve_core_fields')) {
                \Wpo\Services\User_Details_Service::try_improve_core_fields($wpo_usr);

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            return $wpo_usr;
        }

        /**
         * Transform ID token in to internally used User represenation.
         * 
         * @since 14.0
         * 
         * @param   object                  $id_token The open ID connect token received.
         * @return  mixed(User|WP_Error)    A new User object created from the id_token or WP_Error if the ID token could not be parsed
         */
        public static function user_from_b2c_id_token($id_token)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $email = isset($id_token->emails) && \is_array($id_token->emails) && \sizeof($id_token->emails) > 0
                ? trim(strtolower($id_token->emails[0]))
                : '';

            if (empty($email) && !empty($id_token->email)) {
                $email = $id_token->email;
            }

            $preferred_username = $email;

            $upn = isset($id_token->upn)
                ? trim(strtolower($id_token->upn))
                : '';

            $first_name = isset($id_token->given_name)
                ? trim($id_token->given_name)
                : '';

            $last_name = isset($id_token->family_name)
                ? trim($id_token->family_name)
                : '';

            /* $full_name = isset($id_token->name)
                ? trim($id_token->name)
                : ''; */

            $tid = isset($id_token->tid)
                ? trim($id_token->tid)
                : '';

            $oid = isset($id_token->oid)
                ? trim($id_token->oid)
                : '';

            $groups = property_exists($id_token, 'groups') && is_array($id_token->groups)
                ? array_flip($id_token->groups)
                : array();

            $wpo_usr = new User();
            $wpo_usr->from_idp_token = true;
            $wpo_usr->first_name = $first_name;
            $wpo_usr->last_name = $last_name;
            // $wpo_usr->full_name = $full_name;
            $wpo_usr->email = $email;
            $wpo_usr->preferred_username = $preferred_username;
            $wpo_usr->upn = $upn;
            $wpo_usr->name = $upn;
            $wpo_usr->tid = $tid;
            $wpo_usr->oid = $oid;
            $wpo_usr->groups = $groups;

            // Store for later e.g. custom (BuddyPress) fields
            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
            $request->set_item('wpo_usr', $wpo_usr);

            if (Options_Service::get_global_boolean_var('express_login')) {
                return $wpo_usr;
            }

            if (\class_exists('\Wpo\Services\User_Details_Service')) {

                // Either -> Enrich using ID token claims
                if (Options_Service::get_global_string_var('extra_user_fields_source') == 'idToken') {
                    \Wpo\Services\User_Details_Service::get_wpo_usr_from_id_token($wpo_usr, $id_token);
                    // Or -> Enrich using Graph Resource
                } else {
                    $resource_identifier = !empty($wpo_usr->oid)
                        ? $wpo_usr->oid
                        : (
                            (!empty($wpo_usr->upn)
                                ? $wpo_usr->upn
                                : null)
                        );

                    $graph_resource = \Wpo\Services\User_Details_Service::get_graph_user($resource_identifier);

                    if (empty($wpo_usr->upn) && is_array($graph_resource) && array_key_exists('userPrincipalName', $graph_resource)) {
                        $wpo_usr->upn = $graph_resource['userPrincipalName'];
                    }

                    $wpo_usr->graph_resource = $graph_resource;
                }
            }

            // Update cached user
            $request->set_item('wpo_usr', $wpo_usr);

            // Enrich -> Azure AD groups
            if (empty($wpo_usr->groups) && \class_exists('\Wpo\Services\User_Aad_Groups_Service') && \method_exists('\Wpo\Services\User_Aad_Groups_Service', 'get_aad_groups')) {
                \Wpo\Services\User_Aad_Groups_Service::get_aad_groups($wpo_usr);

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            // Improve quality of the data with graph resource
            if (\class_exists('\Wpo\Services\User_Details_Service') && \method_exists('\Wpo\Services\User_Details_Service', 'try_improve_core_fields')) {
                \Wpo\Services\User_Details_Service::try_improve_core_fields($wpo_usr);

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            return $wpo_usr;
        }

        /**
         * Parse graph user response received and return User object. This method may return a user
         * without an email address.
         *
         * @since 2.2
         *
         * @param string 	$user  received from Microsoft Graph
         * @return User  	A new User Object created from the graph response
         */
        public static function user_from_graph_user($graph_resource)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $usr = new User();

            if (empty($graph_resource)) {
                return $usr;
            }

            $is_aad_guest = isset($graph_resource['userPrincipalName']) && false !== stripos($graph_resource['userPrincipalName'], '#ext#');

            $usr->first_name = isset($graph_resource['givenName']) ?  $graph_resource['givenName'] : '';
            $usr->last_name = isset($graph_resource['surname']) ? $graph_resource['surname'] : '';
            $usr->full_name = isset($graph_resource['displayName']) ? $graph_resource['displayName'] : '';
            $usr->email = isset($graph_resource['mail']) ? $graph_resource['mail'] : '';
            $usr->upn = isset($graph_resource['userPrincipalName']) ? $graph_resource['userPrincipalName'] : '';
            $usr->oid = isset($graph_resource['id']) ? $graph_resource['id'] : '';
            $usr->preferred_username = $is_aad_guest && isset($graph_resource['mail']) ? $graph_resource['mail'] : $usr->upn;
            $usr->name = !empty($usr->full_name)
                ? $usr->full_name
                : $usr->preferred_username;
            $usr->graph_resource = $graph_resource;

            // Enrich -> Azure AD groups
            if (\class_exists('\Wpo\Services\User_Aad_Groups_Service') && \method_exists('\Wpo\Services\User_Aad_Groups_Service', 'get_aad_groups')) {
                \Wpo\Services\User_Aad_Groups_Service::get_aad_groups($usr);
            }

            return $usr;
        }

        /**
         * Transform ID token in to internally used User represenation.
         * 
         * @since 7.17
         * 
         * @param $id_token string The open ID connect token received.
         * @return mixed(User|WP_Error) A new User object created from the id_token or WP_Error if the ID token could not be parsed
         */
        public static function user_from_saml_response($name_id, $saml_attributes)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $preferred_username = Saml2_Service::get_attribute('preferred_username', $saml_attributes, true);
            $upn = !empty($name_id) ? $name_id : $preferred_username;
            $email = Saml2_Service::get_attribute('email', $saml_attributes, true);
            $first_name = Saml2_Service::get_attribute('first_name', $saml_attributes);
            $last_name = Saml2_Service::get_attribute('last_name', $saml_attributes);
            $full_name = Saml2_Service::get_attribute('full_name', $saml_attributes);
            $tid = Saml2_Service::get_attribute('tid', $saml_attributes);
            $oid = Saml2_Service::get_attribute('objectidentifier', $saml_attributes);

            $wpo_usr = new User();
            $wpo_usr->from_idp_token = true;
            $wpo_usr->first_name = $first_name;
            $wpo_usr->last_name = $last_name;
            $wpo_usr->full_name = $full_name;
            $wpo_usr->email = $email;
            $wpo_usr->preferred_username = $preferred_username;
            $wpo_usr->upn = $upn;
            $wpo_usr->name = $upn;
            $wpo_usr->tid = $tid;
            $wpo_usr->oid = $oid;

            // Store for later e.g. custom (BuddyPress) fields
            $request_service = Request_Service::get_instance();
            $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
            $request->set_item('wpo_usr', $wpo_usr);

            if (Options_Service::get_global_boolean_var('express_login')) {
                return $wpo_usr;
            }

            // Enrich -> Graph resource for user
            if (\class_exists('\Wpo\Services\User_Details_Service')) {
                $resource_identifier = !empty($wpo_usr->oid)
                    ? $wpo_usr->oid
                    : (
                        (!empty($wpo_usr->upn)
                            ? $wpo_usr->upn
                            : null)
                    );

                $graph_resource = \Wpo\Services\User_Details_Service::get_graph_user($resource_identifier);
                $wpo_usr->graph_resource = $graph_resource;

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            // Enrich -> Azure AD groups
            if (\class_exists('\Wpo\Services\User_Aad_Groups_Service') && \method_exists('\Wpo\Services\User_Aad_Groups_Service', 'get_aad_groups')) {
                \Wpo\Services\User_Aad_Groups_Service::get_aad_groups($wpo_usr);

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            // Improve quality of the data with graph resource
            if (\class_exists('\Wpo\Services\User_Details_Service') && \method_exists('\Wpo\Services\User_Details_Service', 'try_improve_core_fields')) {
                \Wpo\Services\User_Details_Service::try_improve_core_fields($wpo_usr);

                // Update cached user
                $request->set_item('wpo_usr', $wpo_usr);
            }

            return $wpo_usr;
        }

        /**
         * @since 11.0
         */
        public static function ensure_user($wpo_usr)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $wp_usr = self::try_get_user_by($wpo_usr);

            if (!empty($wp_usr)) {

                /**
                 * @since 15.0  Administrators may allow users to sign in and "re-activate" themselves if 
                 *              they can sign in with Microsoft successfully.
                 */
                if (!Options_Service::get_global_boolean_var('allow_reactivation')) {
                    Authentication_Service::is_deactivated($wp_usr->user_login, true);
                }

                \delete_user_meta($wp_usr->ID, 'wpo365_active');

                $wp_usr_id = $wp_usr->ID;
            }

            if (empty($wp_usr)) {
                if (\class_exists('\Wpo\Services\User_Create_Update_Service') && \method_exists('\Wpo\Services\User_Create_Update_Service', 'create_user')) {
                    $wp_usr_id = \Wpo\Services\User_Create_Update_Service::create_user($wpo_usr);
                } else {
                    $wp_usr_id = User_Create_Service::create_user($wpo_usr);
                }
            }

            if (
                !Options_Service::get_global_boolean_var('express_login')
                && class_exists('\Wpo\Services\User_Create_Update_Service') && \method_exists('\Wpo\Services\User_Create_Update_Service', 'update_user')
            ) {
                \Wpo\Services\User_Create_Update_Service::update_user($wp_usr_id, $wpo_usr);
            } else {
                // At the very least add user to current blog
                if (\class_exists('\Wpo\Services\User_Create_Service') && \method_exists('\Wpo\Services\User_Create_Service', 'wpmu_add_user_to_blog')) {
                    \Wpo\Services\User_Create_Service::wpmu_add_user_to_blog($wp_usr_id, $wpo_usr->preferred_username);
                }
            }

            $wp_usr = \get_user_by('ID', $wp_usr_id);

            return $wp_usr;
        }

        /**
         * Tries to find the user by upn, accountname or email.
         * 
         * @since 9.4
         * 
         * @param $wpo_usr
         * 
         * @return WP_User or null
         */
        public static function try_get_user_by($wpo_usr)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $user_match_order = Options_Service::get_global_list_var('user_match_order');

            if (empty($user_match_order)) {
                $user_match_order = array('upn', 'preferred_username', 'email');
            }

            foreach ($user_match_order as $field) {

                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Matching user by field ' . $field);

                if ($field == 'upn') {

                    if (!empty($wpo_usr->upn) && false === stripos($wpo_usr->upn, '#ext#')) {
                        $wp_usr = \get_user_by('login', $wpo_usr->upn);

                        if (!empty($wp_usr)) {
                            return $wp_usr;
                        }
                    }
                } elseif ($field == 'preferred_username') {

                    if (!empty($wpo_usr->preferred_username)) {
                        $wp_usr = \get_user_by('login', $wpo_usr->preferred_username);

                        if (!empty($wp_usr)) {
                            return $wp_usr;
                        }
                    }
                } elseif ($field == 'email') {

                    if (!empty($wpo_usr->email)) {
                        $wp_usr = \get_user_by('email', $wpo_usr->email);

                        if (!empty($wp_usr)) {
                            return $wp_usr;
                        }
                    }
                } elseif ($field == 'login') {

                    if (!empty($wpo_usr->preferred_username)) {

                        $atpos = strpos($wpo_usr->preferred_username, '@');

                        if (false !== $atpos) {
                            $accountname = substr($wpo_usr->preferred_username, 0, $atpos);
                            $wp_usr = \get_user_by('login', $accountname);

                            if (!empty($wp_usr)) {
                                return $wp_usr;
                            }
                        }
                    }
                }
            }

            return null;
        }

        /**
         * @since 11.0
         */
        public static function try_get_user_principal_name($wp_usr_id)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (empty($wp_usr_id)) {
                $request_service = Request_Service::get_instance();
                $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
                $wpo_usr = $request->get_item('wpo_usr');

                if (!empty($wpo_usr) && !empty($wpo_usr->upn)) {
                    return $wpo_usr->upn;
                }
            }

            $upn = get_user_meta($wp_usr_id, 'userPrincipalName', true);

            if (empty($upn)) {
                $wp_usr = \get_user_by('ID', $wp_usr_id);
                $upn = $wp_usr->user_login;
                $smtp_domain = Domain_Helpers::get_smtp_domain_from_email_address($upn);

                // User's login cannot be used to identify the user resource
                if (empty($smtp_domain) || !Domain_Helpers::is_tenant_domain($smtp_domain)) {
                    $upn = $wp_usr->user_email;
                    $smtp_domain = Domain_Helpers::get_smtp_domain_from_email_address($upn);

                    if (empty($smtp_domain) || !Domain_Helpers::is_tenant_domain($smtp_domain)) {
                        return null;
                    }
                }
            }

            return $upn;
        }

        /**
         * @since 11.0
         */
        public static function save_user_principal_name($upn)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $wp_usr_id = get_current_user_id();

            if ($wp_usr_id > 0 && !empty($upn)) {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Successfully saved upn ' . $upn);
                update_user_meta($wp_usr_id, 'userPrincipalName', $upn);
            }
        }

        /**
         * @since 11.0
         */
        public static function try_get_user_tenant_id($wp_usr_id)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (empty($wp_usr_id)) {
                $request_service = Request_Service::get_instance();
                $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
                $wpo_usr = $request->get_item('wpo_usr');

                if (!empty($wpo_usr) && !empty($wpo_usr->tid)) {
                    return $wpo_usr->tid;
                }
            }

            $tid = get_user_meta($wp_usr_id, 'aadTenantId', true);

            if (empty($tid)) {
                $tid = Options_Service::get_aad_option('tenant_id');
            }

            return $tid;
        }

        /**
         * @since 11.0
         */
        public static function save_user_tenant_id($tid)
        {
            $wp_usr_id = get_current_user_id();

            if ($wp_usr_id > 0 && !empty($tid)) {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Successfully saved user tenant id ' . $tid);
                update_user_meta($wp_usr_id, 'aadTenantId', $tid);
            }
        }

        /**
         * Tries to retrieve a user's Azure AD object ID stored as user meta when the user last logged in.
         * 
         * @since 12.10
         * 
         * @param   $wp_usr_id  int     The user's WP_User ID
         * @return  mixed(string|null)  GUID as string or null if not found
         */
        public static function try_get_user_object_id($wp_usr_id)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (empty($wp_usr_id)) {
                $request_service = Request_Service::get_instance();
                $request = $request_service->get_request($GLOBALS['WPO_CONFIG']['request_id']);
                $wpo_usr = $request->get_item('wpo_usr');

                if (!empty($wpo_usr) && !empty($wpo_usr->oid)) {
                    return $wpo_usr->oid;
                }
            }

            $oid = get_user_meta($wp_usr_id, 'aadObjectId', true);

            if (empty($oid)) {
                $oid = null;
            }

            return $oid;
        }

        /**
         * @since 11.0
         */
        public static function save_user_object_id($oid)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $wp_usr_id = get_current_user_id();

            if ($wp_usr_id > 0 && !empty($oid)) {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Successfully saved user object id ' . $oid);
                update_user_meta($wp_usr_id, 'aadObjectId', $oid);
            }
        }

        /**
         * Checks whether current user is O365 user
         *
         * @since   1.0
         * @return  int One of the following User Service class constants 
         *              USER_NOT_LOGGED_IN, IS_O365_USER or IS_NOT_O365_USER
         */
        public static function user_is_o365_user($user_id, $email = '')
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $wp_usr = get_user_by('ID', intval($user_id));

            if (!empty($email) && false === $wp_usr) {
                $wp_usr = get_user_by('email', $email);
            }

            if ($wp_usr === false) {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Checking whether user is O365 user -> Not logged on');
                return self::USER_NOT_LOGGED_IN;
            }

            $email_domain = Domain_Helpers::get_smtp_domain_from_email_address($wp_usr->user_email);

            if (Domain_Helpers::is_tenant_domain($email_domain)) {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Checking whether user is O365 user -> YES');
                return self::IS_O365_USER;
            }

            Log_Service::write_log('DEBUG', __METHOD__ . ' -> Checking whether user is O365 user -> NO');
            return self::IS_NOT_O365_USER;
        }


        /**
         * 
         * @param mixed $oid 
         * @return mixed 
         */
        public static function get_user_by_oid($wpo_usr)
        {

            if (empty($wpo_usr) || empty($wpo_usr->oid)) {
                return null;
            }

            $args = array(
                'meta_key' => 'aadObjectId',
                'meta_value' => $wpo_usr->oid
            );

            $users = get_users($args);

            if (sizeof($users) !== 1) {
                return null;
            }

            return $users[0];
        }

        /**
         * Helper to get a property value of an object or otherwise return a default value.
         * 
         * @param   $resource       object  Object that is the parent of the property
         * @param   $prop           string  Name of the property
         * @param   $default        mixed   Default value if property does not exist
         * @param   $tolower        boolean Whether or not to change the casing of the return value to lower
         * @param   $log_message    string  Message to write to the log if the property does not exist
         */
        private static function get_property_or_default(
            $resource,
            $prop,
            $default = '',
            $tolower = false,
            $log_message = ''
        ) {

            if (isset($resource->$prop)  && !empty($resource->$prop)) {
                return $tolower && is_string($resource->$prop)
                    ? strtolower(trim($resource->$prop))
                    : (
                        (is_string($resource->$prop)
                            ? trim($resource->$prop)
                            : $resource->$prop)
                    );
            }

            if (!empty($log_message)) {
                Log_Service::write_log('WARN', __METHOD__ . " -> $log_message");
            }

            return $default;
        }

        /**
         * Helper to get a property value of an object or otherwise return a default value.
         * 
         * @param   $resource       array   (Associative) array that is the parent of the property
         * @param   $prop           string  Name of the property
         * @param   $default        mixed   Default value if property does not exist
         * @param   $tolower        boolean Whether or not to change the casing of the return value to lower
         * @param   $log_message    string  Message to write to the log if the property does not exist
         */
        private static function get_arr_property_or_default(
            $resource,
            $prop,
            $default = '',
            $tolower = false,
            $log_message = ''
        ) {
            if (isset($resource[$prop]) && !empty($resource[$prop])) {
                return $tolower && is_string($resource[$prop])
                    ? strtolower(trim($resource[$prop]))
                    : (
                        (is_string($resource[$prop])
                            ? trim($resource[$prop])
                            : $resource[$prop])
                    );
            }
            Log_Service::write_log('WARN', __METHOD__ . " -> $log_message");
            return $default;
        }
    }
}
