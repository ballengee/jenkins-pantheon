<?php

namespace Wpo\Services;

// Prevent public access to this script
defined('ABSPATH') or die();

use \Wpo\Core\Url_Helpers;
use \Wpo\Core\Wpmu_Helpers;
use \Wpo\Services\Log_Service;
use \Wpo\Services\Options_Service;

if (!class_exists('\Wpo\Services\User_Create_Service')) {

    class User_Create_Service
    {

        /**
         * @since 11.0
         */
        public static function create_user(&$wpo_usr)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            $user_login = !empty($wpo_usr->preferred_username)
                ? $wpo_usr->preferred_username
                : $wpo_usr->upn;

            /**
             * @since 12.5 
             * 
             * Don't create a user when that user should not be added to a subsite in case of wpmu shared mode.
             */
            if (is_multisite() && !Options_Service::mu_use_subsite_options() && !is_main_site() && Options_Service::get_global_boolean_var('skip_add_user_to_subsite')) {
                $blog_id = get_current_blog_id();

                // Not using subsite options and administrator has disabled automatic adding of users to subsites
                Log_Service::write_log('WARN', __METHOD__ . " -> Skipped creating a user with login $user_login for blog with ID $blog_id because administrator has disabled adding a user to a subsite");
                Authentication_Service::goodbye(Error_Service::USER_NOT_FOUND);
                exit();
            }

            if (!Options_Service::get_global_boolean_var('create_and_add_users')) {
                Log_Service::write_log('ERROR', __METHOD__ . ' -> User not found and settings prevented creating a new user on-demand for user ' . $user_login);
                Authentication_Service::goodbye(Error_Service::USER_NOT_FOUND);
                exit();
            }

            $usr_default_role = is_main_site()
                ? Options_Service::get_global_string_var('new_usr_default_role')
                : Options_Service::get_global_string_var('mu_new_usr_default_role');

            $password_length = Options_Service::get_global_numeric_var('password_length');

            if (empty($password_length) || $password_length < 16) {
                $password_length = 16;
            }

            $userdata = array(
                'user_login'    => $user_login,
                'user_pass'     => wp_generate_password($password_length, true, false),
                'role'          => $usr_default_role,
            );

            /**
             * @since 9.4 
             * 
             * Optionally removing any user_register hooks as these more often than
             * not interfer and cause unexpected behavior.
             */

            $user_regiser_hooks = null;

            if (Options_Service::get_global_boolean_var('skip_user_register_action') && isset($GLOBALS['wp_filter']) && isset($GLOBALS['wp_filter']['user_register'])) {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Temporarily removing all filters for the user_register action to avoid interference');
                $user_regiser_hooks = $GLOBALS['wp_filter']['user_register'];
                unset($GLOBALS['wp_filter']['user_register']);
            }

            // Insert in Wordpress DB
            $wp_usr_id = wp_insert_user($userdata);

            if (!empty($GLOBALS['wp_filter']) && !empty($user_regiser_hooks)) {
                $GLOBALS['wp_filter']['user_register'] = $user_regiser_hooks;
            }

            if (is_wp_error($wp_usr_id)) {
                Log_Service::write_log('ERROR', __METHOD__ . ' -> Could not create wp user. See next line for error information.');
                Log_Service::write_log('ERROR', $wp_usr_id);
                Authentication_Service::goodbye(Error_Service::CHECK_LOG);
                exit();
            }

            /**
             * @since 15.0
             */

            do_action('wpo365/user/created', $wp_usr_id);

            $wpo_usr->created = true;
            Log_Service::write_log('DEBUG', __METHOD__ . ' -> Created new user with ID ' . $wp_usr_id);

            self::wpmu_add_user_to_blog($wp_usr_id, $user_login);

            // Try and send new user email
            if (\class_exists('\Wpo\Services\Mail_Notifications_Service')) {

                if (Options_Service::get_global_boolean_var('new_usr_send_mail')) {
                    $notify = Options_Service::get_global_boolean_var('new_usr_send_mail_admin_only')
                        ? 'admin'
                        : 'both';
                    \Wpo\Services\Mail_Notifications_Service::new_user_notification($wp_usr_id, null, $notify);
                    Log_Service::write_log('DEBUG', __METHOD__ . ' -> Sent new user notification');
                }
            } else {
                Log_Service::write_log('DEBUG', __METHOD__ . ' -> Did not sent new user notification');
            }

            Wpmu_Helpers::mu_delete_transient('wpo365_upgrade_dismissed');
            Wpmu_Helpers::mu_set_transient('wpo365_user_created', date('d'), 1209600);

            return $wp_usr_id;
        }

        /**
         * @since 11.0
         */
        public static function wpmu_add_user_to_blog($wp_usr_id, $preferred_user_name)
        {
            Log_Service::write_log('DEBUG', '##### -> ' . __METHOD__);

            if (!is_multisite()) {
                return;
            }

            $blog_id = get_current_blog_id();
            $is_main_site = is_main_site();

            $usr_default_role = $is_main_site
                ? Options_Service::get_global_string_var('new_usr_default_role')
                : Options_Service::get_global_string_var('mu_new_usr_default_role');

            if (!empty($usr_default_role)) {

                if (!is_user_member_of_blog($wp_usr_id, $blog_id)) {

                    $use_subsite_options = Options_Service::mu_use_subsite_options();
                    $add_member_to_main_site = Options_Service::get_global_boolean_var('create_and_add_users');
                    $add_member_to_subsite = !Options_Service::get_global_boolean_var('skip_add_user_to_subsite');

                    // Settings don't allow adding member to main site [wpmu shared mode]
                    if (!$use_subsite_options && $is_main_site && !$add_member_to_main_site) {
                        Log_Service::write_log('ERROR', __METHOD__ . ' -> [WPMU shared / main site] User not a member of blog with id ' . $blog_id . ' and settings prevented adding user ' . $wp_usr_id);
                        Authentication_Service::goodbye(Error_Service::USER_NOT_FOUND);
                        exit();
                    }

                    // Settings don't allow adding member to sub site [wpmu shared mode]
                    if (!$use_subsite_options && !$is_main_site && !$add_member_to_subsite) {
                        Log_Service::write_log('ERROR', __METHOD__ . ' -> [WPMU shared / subsite] User not a member of blog with id ' . $blog_id . ' and settings prevented adding user ' . $wp_usr_id);
                        Authentication_Service::goodbye(Error_Service::USER_NOT_FOUND);
                        exit();
                    }

                    // Settings don't allow adding member to dedicated site [wpmu dedicated mode]
                    if ($use_subsite_options && !$add_member_to_main_site) {
                        Log_Service::write_log('ERROR', __METHOD__ . ' -> [WPMU dedicated] User not a member of blog with id ' . $blog_id . ' and settings prevented adding user ' . $wp_usr_id);
                        Authentication_Service::goodbye(Error_Service::USER_NOT_FOUND);
                        exit();
                    }

                    add_user_to_blog($blog_id, $wp_usr_id, $usr_default_role);

                    /**
                     * @since 15.0
                     */

                    do_action('wpo365/wpmu/user_added', $blog_id, $wp_usr_id);

                    Log_Service::write_log('DEBUG', __METHOD__ . " -> Added user with ID $wp_usr_id as a member to blog with ID $blog_id");
                } else {
                    Log_Service::write_log('DEBUG', __METHOD__ . " -> Skipped adding user with ID $wp_usr_id to blog with ID $blog_id because user already added");
                }
            } else {
                Log_Service::write_log('WARN', __METHOD__ . ' -> Could not add user ' . $preferred_user_name . ' to current blog with ID ' . $blog_id . ' because the default role for the subsite is not valid');
            }
        }
    }
}
