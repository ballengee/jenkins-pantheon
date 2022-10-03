<?php

namespace Wpo\Core;

use \Wpo\Core\Permissions_Helpers;
use \Wpo\Services\Options_Service;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Core\Wp_Hooks')) {

    class Wp_Hooks
    {

        public static function add_wp_hooks()
        {
            // Do super admin stuff
            if ((is_admin() || is_network_admin()) && Permissions_Helpers::user_is_admin(\wp_get_current_user())) {

                if (class_exists('\Wpo\Core\Plugin_Updater')) {
                    add_action('admin_init', '\Wpo\Core\Plugin_Updater::check_for_updates');
                }

                if (class_exists('\Wpo\Sync\Sync_Admin_Page') && Options_Service::get_global_boolean_var('enable_user_sync')) {
                    add_action('admin_menu', '\Wpo\Sync\Sync_Admin_Page::add_plugin_page', 10);
                    add_action('init', '\Wpo\Sync\Sync_Admin_Page::init', 10, 0);
                }

                // Add and hide wizard (page)
                add_action('admin_menu', '\Wpo\Pages\Wizard_Page::add_management_page');
                add_action('network_admin_menu', '\Wpo\Pages\Wizard_Page::add_management_page');

                new \Wpo\Pages\License_Page();

                // Show admin notification when WPO365 not properly configured
                add_action('admin_notices', '\Wpo\Services\Notifications_Service::show_admin_notices', 10, 0);
                add_action('network_admin_notices', '\Wpo\Services\Notifications_Service::show_admin_notices', 10, 0);
                add_action('admin_init', '\Wpo\Services\Notifications_Service::dismiss_admin_notices', 10, 0);

                // Add license related messages to WP Admin
                \Wpo\Core\Plugin_Updater::show_license_notices();

                // Wire up AJAX backend services
                add_action('wp_ajax_wpo365_delete_settings', '\Wpo\Services\Ajax_Service::delete_settings');
                add_action('wp_ajax_wpo365_delete_tokens', '\Wpo\Services\Ajax_Service::delete_tokens');
                add_action('wp_ajax_wpo365_get_settings', '\Wpo\Services\Ajax_Service::get_settings');
                add_action('wp_ajax_wpo365_update_settings', '\Wpo\Services\Ajax_Service::update_settings');
                add_action('wp_ajax_wpo365_get_log', '\Wpo\Services\Ajax_Service::get_log');
                add_action('wp_ajax_wpo365_get_self_test_results', '\Wpo\Services\Ajax_Service::get_self_test_results');

                // Graph mailer
                add_action('wp_ajax_wpo365_send_test_mail', '\Wpo\Services\Ajax_Service::send_test_mail');
                add_action('wp_ajax_wpo365_get_mail_authorization_url', '\Wpo\Services\Ajax_Service::get_mail_authorization_url');
                add_action('wp_ajax_wpo365_get_mail_auth_configuration', '\Wpo\Services\Ajax_Service::get_mail_auth_configuration');

                // Graph mailer auditing
                if (class_exists('\Wpo\Mail\Mail_Db')) {
                    add_action('wp_ajax_wpo365_get_mail_log', '\Wpo\Mail\Mail_Ajax_Service::get_mail_log');
                    add_action('wp_ajax_wpo365_send_mail_again', '\Wpo\Mail\Mail_Ajax_Service::send_mail_again');
                    add_action('wp_ajax_wpo365_truncate_mail_log', '\Wpo\Mail\Mail_Ajax_Service::truncate_mail_log');
                }

                // Show settings link
                add_filter((is_network_admin() ? 'network_admin_' : '') . 'plugin_action_links_' . $GLOBALS['WPO_CONFIG']['plugin'], '\Wpo\Core\Plugin_Helpers::get_configuration_action_link', 10, 1);
            }

            if (class_exists('\Wpo\Services\Auth_Only_Service')) {
                $scenario = Options_Service::get_global_string_var('auth_scenario');

                if ($scenario == 'internetAuthOnly' || $scenario == 'intranetAuthOnly') {
                    add_filter('wpo365/cookie/redirect', '\Wpo\Services\Auth_Only_Service::cookie_redirect', 10);
                    add_filter('wpo365/cookie/set', '\Wpo\Services\Auth_Only_Service::set_wpo_cookie', 10, 2);
                    add_filter('wpo365_skip_authentication', '\Wpo\Services\Auth_Only_Service::validate_auth_cookie', 10);
                    add_filter('wpo365/cookie/remove/url', '\Wpo\Services\Auth_Only_Service::remove_cookie_from_url', 10, 1);
                }
            }

            // Add custom cron schedule for user sync
            if (class_exists('\Wpo\Core\Cron_Helpers')) {
                // Filter to add custom cron schedules
                add_filter('cron_schedules', '\Wpo\Core\Cron_Helpers::add_cron_schedules', 10, 1);
            }

            // Hooks used by cron jobs to schedule user synchronization events
            if (class_exists('\Wpo\Sync\Sync_Manager')) {
                add_action('wpo_sync_users', '\Wpo\Sync\Sync_Manager::fetch_users', 10, 3);
                add_action('wpo_sync_users_start', '\Wpo\Sync\Sync_Manager::fetch_users', 10, 2);
            }

            // Hooks used by cron jobs to schedule user synchronization events
            if (class_exists('\Wpo\Sync\SyncV2_Service')) {
                add_action('wpo_sync_v2_users_start', '\Wpo\Sync\SyncV2_Service::sync_users', 10, 1);
                add_action('wpo_sync_v2_users_next', '\Wpo\Sync\SyncV2_Service::fetch_users', 10, 2);
            }

            // Ensure session is valid and remains valid
            add_action('destroy_wpo365_session', '\Wpo\Services\Authentication_Service::destroy_session');

            // Prevent email address update
            add_action('personal_options_update', '\Wpo\Core\Permissions_Helpers::prevent_email_change', 10, 1);

            // Redirect when user is not logged in and tries to navigate to a private page
            add_action('posts_selection', '\Wpo\Services\Authentication_Service::check_private_pages');

            // Add short code(s)
            add_action('init', 'Wpo\Core\Shortcode_Helpers::ensure_pintra_short_code');
            add_action('init', 'Wpo\Core\Shortcode_Helpers::ensure_display_error_message_short_code');
            add_action('init', 'Wpo\Core\Shortcode_Helpers::ensure_login_button_short_code');
            add_action('init', 'Wpo\Core\Shortcode_Helpers::ensure_login_button_short_code_V2');

            // Wire up AJAX backend services
            add_action('wp_ajax_get_tokencache', '\Wpo\Services\Ajax_Service::get_tokencache');
            add_action('wp_ajax_cors_proxy', '\Wpo\Services\Ajax_Service::cors_proxy');

            // Register custom post meta for Audiences
            if (class_exists('\Wpo\Services\Audiences_Service')) {
                // Filters
                add_filter('manage_users_columns', '\Wpo\Services\Audiences_Service::register_users_audiences_column', 10);
                add_filter('manage_users_custom_column', '\Wpo\Services\Audiences_Service::render_users_audiences_column', 10, 3);
                add_filter('posts_where', '\Wpo\Services\Audiences_Service::posts_where', 10, 2);
                add_filter('the_posts', '\Wpo\Services\Audiences_Service::the_posts', 10, 2);
                add_filter('get_pages', '\Wpo\Services\Audiences_Service::get_pages', 10, 2);
                add_filter('wp_count_posts', '\Wpo\Services\Audiences_Service::wp_count_posts', 10, 3);
                add_filter('get_previous_post_where', '\Wpo\Services\Audiences_Service::get_previous_post_where', 10, 5);
                add_filter('get_next_post_where', '\Wpo\Services\Audiences_Service::get_next_post_where', 10, 5);

                // Actions
                add_action('init', '\Wpo\Services\Audiences_Service::aud_register_post_meta', 10);
                add_action('post_updated', '\Wpo\Services\Audiences_Service::post_updated', 10, 3);

                if (\method_exists('\Wpo\Services\Audiences_Service', 'audiences_add_meta_box')) {
                    add_action('add_meta_boxes', '\Wpo\Services\Audiences_Service::audiences_add_meta_box', 10, 2);
                }

                if (\method_exists('\Wpo\Services\Audiences_Service', 'audiences_save_post')) {
                    add_action('save_post', '\Wpo\Services\Audiences_Service::audiences_save_post', 10, 3);
                }

                // WP-REST

                add_action('rest_api_init', function () {
                    define('WPO365_REST_REQUEST', true);
                });

                if (Options_Service::get_global_boolean_var('enable_audiences_rest')) {

                    $post_types = $post_types = get_post_types();

                    foreach ($post_types as $post_type) {

                        add_filter('rest_prepare_{' . $post_type . '}', '\Wpo\Services\Audiences_Service::rest_prepare_post', 10, 3);
                    }
                }
            }

            // Clean up on shutdown
            add_action('shutdown', '\Wpo\Services\Request_Service::shutdown');

            // Add pintraredirectjs
            add_action('wp_enqueue_scripts', '\Wpo\Core\Script_Helpers::enqueue_pintra_redirect', 10, 0);
            add_action('login_enqueue_scripts', '\Wpo\Core\Script_Helpers::enqueue_pintra_redirect', 10, 0);
            add_action('admin_enqueue_scripts', '\Wpo\Core\Script_Helpers::enqueue_pintra_redirect', 10, 0);
            add_action('admin_enqueue_scripts', '\Wpo\Core\Script_Helpers::enqueue_wizard', 10, 0);
            add_filter('script_loader_tag', '\Wpo\Core\Script_Helpers::enqueue_script_asynchronously', 10, 3);

            // Add safe style css
            add_filter('safe_style_css', '\Wpo\Core\WordPress_Helpers::safe_css', 10, 1);

            // Adds the login button
            add_action('login_form', '\Wpo\Core\Shortcode_Helpers::login_button', 10);

            // Init the custom REST API for config
            if (class_exists('\Wpo\Core\Config_Controller')) {
                add_action('rest_api_init', function () {
                    $config_controller = new \Wpo\Core\Config_Controller();
                    $config_controller->register_routes();
                });
            }

            // Init the custom REST API for user sync
            if (class_exists('\Wpo\Sync\SyncV2_Controller')) {
                add_action('rest_api_init', function () {
                    $sync_controller = new \Wpo\Sync\SyncV2_Controller();
                    $sync_controller->register_routes();
                });
            }

            // Init the custom REST API for PINTRA
            if (class_exists('\Wpo\Graph\Controller') && Options_Service::get_global_boolean_var('enable_graph_api')) {
                add_action('rest_api_init', function () {
                    $graph_controller = new \Wpo\Graph\Controller();
                    $graph_controller->register_routes();
                });
            }

            // Enable X-WP-NONCE (cookies) protection for WordPress REST API
            if (Options_Service::get_global_boolean_var('use_wp_rest_cookies')) {
                add_filter('rest_authentication_errors', '\Wpo\Services\Rest_Authentication_Service_Cookies::authenticate_request', 10, 1);
            }

            // Enable Azure AD protection for WordPress REST API
            if (class_exists('\Wpo\Services\Rest_Authentication_Service_Aad') && Options_Service::get_global_boolean_var('use_wp_rest_aad')) {
                add_filter('rest_authentication_errors', '\Wpo\Services\Rest_Authentication_Service_Aad::authenticate_request', 10, 1);
            }

            if (class_exists('\Wpo\Services\User_Custom_Fields_Service')) {
                // Add extra user profile fields
                add_action('show_user_profile', '\Wpo\Services\User_Custom_Fields_Service::show_extra_user_fields', 10, 1);
                add_action('edit_user_profile', '\Wpo\Services\User_Custom_Fields_Service::show_extra_user_fields', 10, 1);
                add_action('personal_options_update', '\Wpo\Services\User_Custom_Fields_Service::save_user_details', 10, 1);
                add_action('edit_user_profile_update', '\Wpo\Services\User_Custom_Fields_Service::save_user_details', 10, 1);
            }

            if (class_exists('\Wpo\Services\Login_Service')) {
                // Prevent WP default login for O365 accounts
                add_action('wp_authenticate', '\Wpo\Services\Login_Service::prevent_default_login_for_o365_users', 11, 1);
            }

            // Hide the admin Bar
            add_action('after_setup_theme', '\Wpo\Core\WordPress_Helpers::hide_admin_bar', 10);

            if (class_exists('\Wpo\Services\Authentication_Service')) {
                // Prevent WP login for deactivated users
                add_action('wp_authenticate', '\Wpo\Services\Authentication_Service::is_deactivated', 10, 1);
            }

            if (class_exists('\Wpo\SCIM\SCIM_Controller')) {
                // Init the custom REST API for SCIM
                add_action('rest_api_init', function () {
                    $scim_controller = new \Wpo\SCIM\SCIM_Controller();
                    $scim_controller->register_routes();
                });
            }

            // Replace phpmailer with Graph Mailer (but only if configured)
            // According to https://developer.wordpress.org/reference/functions/wp_mail/ wp_mail is available after plugins_loaded
            add_action('phpmailer_init', '\Wpo\Mail\Mailer::init', 50);
            add_filter('wp_mail_from', '\Wpo\Mail\Mailer::mail_from', 10, 1);

            if (class_exists('\Wpo\Mail\Mail_Db')) {
                // Log each wp_mail in the wpo365_table
                add_filter('wp_mail', '\Wpo\Mail\Mail_Db::add_mail_log', 10, 1);
            }

            // BUDDY PRESS
            if (class_exists('\Wpo\Services\BuddyPress_Service')) {
                // Add extra user profile fields to Buddy Press
                add_action('bp_after_profile_loop_content', '\Wpo\Services\BuddyPress_Service::bp_show_extra_user_fields', 10, 1);
                // Replace avatar with O365 avatar (if available)
                add_filter('bp_core_fetch_avatar', '\Wpo\Services\BuddyPress_Service::fetch_buddy_press_avatar', 99, 2);
            }

            // Only allow password changes for non-O365 users and only when already logged on to the system
            add_filter('show_password_fields',  '\Wpo\Core\Permissions_Helpers::show_password_fields', 10, 2);
            add_filter('allow_password_reset', '\Wpo\Core\Permissions_Helpers::allow_password_reset', 10, 2);

            // Enable login message output
            add_filter('login_message', '\Wpo\Services\Error_Service::check_for_login_messages', 10, 1);

            // Add custom wp query vars
            add_filter('query_vars', '\Wpo\Core\Url_Helpers::add_query_vars_filter');

            if (class_exists('\Wpo\Services\User_Details_Service')) {
                add_filter('send_email_change_email', '\Wpo\Services\User_Details_Service::prevent_send_email_change_email');
            }

            if (Options_Service::get_global_boolean_var('prevent_send_password_change_email')) {
                remove_all_actions('after_password_reset');
            }

            if (class_exists('\Wpo\Services\Avatar_Service')) {
                // Replace avatar with O365 avatar (if available)
                $avatar_hook_priority = Options_Service::get_global_numeric_var('avatar_hook_priority', false);
                $avatar_hook_priority = $avatar_hook_priority > 0 ? $avatar_hook_priority : 1;
                add_filter('get_avatar', '\Wpo\Services\Avatar_Service::get_O365_avatar', $avatar_hook_priority, 3);
            }

            if (class_exists('\Wpo\Services\Mail_Notifications_Service')) {
                // Filter to change the new user email notification
                add_filter('wp_new_user_notification_email', '\Wpo\Services\Mail_Notifications_Service::new_user_notification_email', 99, 3);
            }

            // Must be added before the next wp_logout hook
            add_action('wp_logout', '\Wpo\Services\Authentication_Service::destroy_session', 1, 0);

            if (class_exists('\Wpo\Services\Logout_Service')) {
                add_action('wp_logout', '\Wpo\Services\Logout_Service::logout_O365', 1, 0);
                add_action('wp_logout', '\Wpo\Services\Logout_Service::send_to_custom_logout_page', 2, 0);
            }

            // To support single sign out without user confirmation
            if (class_exists('\Wpo\Services\Redirect_Service')) {
                add_action('check_admin_referer', '\Wpo\Services\Redirect_Service::logout_without_confirmation', 10, 2);
            }
        }
    }
}
