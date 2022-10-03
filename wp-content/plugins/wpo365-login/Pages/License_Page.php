<?php

namespace Wpo\Pages;

use \Wpo\Core\Extensions_Helpers;
use \Wpo\Core\Wpmu_Helpers;
use \Wpo\Services\Options_Service;
use \Wpo\Services\Log_Service;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Pages\License_Page')) {

    class License_Page
    {

        private static $extensions = array();

        public function __construct()
        {

            /**
             * Load custom updater.
             */

            // Multisite frontend
            if (is_multisite() && !is_network_admin()) {
                return;
            }

            // Single site frontend
            if (!is_multisite() && !is_admin()) {
                return;
            }

            // Collect information about all activated extensions
            self::$extensions = Extensions_Helpers::get_extensions();

            // No extensions so no need to add the license page
            if (empty(self::$extensions)) {
                return;
            }

            /**
             * Add admin page.
             */
            add_action('admin_menu', '\Wpo\Pages\License_Page::license_menu');
            add_action('network_admin_menu', '\Wpo\Pages\License_Page::license_menu');

            /**
             * Activate license.
             */
            add_action('admin_init', '\Wpo\Pages\License_Page::activate_license');

            /**
             * Deactivate license.
             */
            add_action('admin_init', '\Wpo\Pages\License_Page::deactivate_license');

            /**
             * Show activation result.
             */
            add_action('admin_notices', '\Wpo\Pages\License_Page::activation_notice');
            add_action('network_admin_notices', '\Wpo\Pages\License_Page::activation_notice');
        }

        /**
         * Adds a "Licenses" submenu page to the main WPO365 admin menu.
         */
        public static function license_menu()
        {
            add_submenu_page('wpo365-wizard', 'Licenses', 'Licenses', 'delete_users', 'wpo365-manage-licenses', '\Wpo\Pages\License_Page::license_page');
        }

        public static function activation_notice()
        {

            if (isset($_GET['sl_activation']) && !empty($_GET['message']) && isset($_GET['page']) && $_GET['page'] == 'wpo365-manage-licenses') {

                $message = sanitize_text_field(urldecode($_GET['message']));

                switch ($_GET['sl_activation']) {

                    case 'false': ?>
                        <div class="notice notice-error">
                            <p><?php echo esc_html($message); ?></p>
                        </div>
                    <?php
                        break;

                    case 'true':
                    default:
                    ?>
                        <div class="notice notice-success"><?php echo esc_html($message) ?></div>
            <?php
                        break;
                }
            }
        }

        public static function activate_license()
        {

            // listen for our activate button to be clicked
            if (isset($_POST['activate_license']) && isset($_POST['store_item_id'])) {

                foreach (self::$extensions as $slug => $data) {

                    if ($data['store_item_id'] === intval(trim($_POST['store_item_id']))) {
                        $extension = $data;
                        break;
                    }
                }

                if (empty($extension)) {
                    Log_Service::write_log('WARN', __METHOD__ . ' -> Could not find extension for store item id ' . trim(sanitize_text_field($_POST['store_item_id'])));
                    return;
                }

                // run a quick security check
                if (!check_admin_referer('wpo365-manage-licenses', 'wpo365_license_nonce')) {
                    Log_Service::write_log('WARN', __METHOD__ . ' -> Could not successfully verify nonce [check admin referrer failed]');
                    return;
                }

                // retrieve the license from the POSTed data
                $license_key = 'license_' . $extension['store_item_id'];
                $license = sanitize_text_field(trim($_POST[$license_key]));

                // Call the custom API.
                $response = wp_remote_get(\sprintf("https://www.wpo365.com/?edd_action=activate_license&license=%s&item_id=%s&url=%s", $license, $extension['store_item_id'], home_url()), array('timeout' => 15, 'sslverify' => false));

                // make sure the response came back okay
                if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {

                    if (is_wp_error($response)) {
                        $message = $response->get_error_message();
                    } else {
                        Log_Service::write_log('ERROR', __METHOD__ . ' -> Error occurred when activating license. Check the next line for the raw response message received.');
                        Log_Service::write_log('ERROR', $response);

                        $message = __('An error occurred, please try again.');
                    }
                } else {

                    $license_data = json_decode(wp_remote_retrieve_body($response));

                    if (false === $license_data->success) {

                        switch ($license_data->error) {

                            case 'expired':

                                $message = sprintf(
                                    __('Your license key expired on %s.'),
                                    date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
                                );
                                break;

                            case 'disabled':
                            case 'revoked':

                                $message = __('Your license key has been disabled.');
                                break;

                            case 'missing':

                                $message = __('Invalid license.');
                                break;

                            case 'invalid':
                            case 'site_inactive':

                                $message = __('Your license is not active for this URL.');
                                break;

                            case 'item_name_mismatch':

                                $message = sprintf(__('This appears to be an invalid license key for %s.'), EDD_SAMPLE_ITEM_NAME);
                                break;

                            case 'no_activations_left':

                                $message = __('Your license key has reached its activation limit.');
                                break;

                            default:

                                Log_Service::write_log('ERROR', __METHOD__ . ' -> Error occurred when activating license. Check the next line for the license data received.');
                                Log_Service::write_log('ERROR', $license_data);

                                $message = __('An error occurred, please try again.');
                                break;
                        }
                    }
                }

                $option_name = 'license_' . $extension['store_item_id'];
                $base_url = is_multisite()
                    ? network_admin_url('admin.php?page=wpo365-manage-licenses')
                    : admin_url('admin.php?page=wpo365-manage-licenses');

                if (!empty($message)) {
                    Options_Service::add_update_option($option_name, '');
                    $redirect = add_query_arg(array('sl_activation' => 'false', 'message' => urlencode($message)), $base_url);
                } else {
                    Options_Service::add_update_option($option_name, $license);
                    $redirect = add_query_arg(array('sl_activation' => 'true', 'message' => urlencode('License for ' . $extension['store_item'] . ' has been successfully activated.')), $base_url);
                }

                Wpmu_Helpers::mu_delete_transient('wpo365_lic_notices');

                wp_redirect($redirect);
                exit();
            }
        }

        public static function deactivate_license()
        {

            if (isset($_POST['deactivate_license']) && isset($_POST['store_item_id'])) {

                foreach (self::$extensions as $slug => $data) {

                    if ($data['store_item_id'] === intval(trim($_POST['store_item_id']))) {
                        $extension = $data;
                        break;
                    }
                }

                if (empty($extension)) {
                    Log_Service::write_log('WARN', __METHOD__ . ' -> Could not find extension for store item id ' . sanitize_text_field(trim($_POST['store_item_id'])));
                    return;
                }

                // run a quick security check
                if (!check_admin_referer('wpo365-manage-licenses', 'wpo365_license_nonce')) {
                    Log_Service::write_log('WARN', __METHOD__ . ' -> Could not successfully verify nonce [check admin referrer failed]');
                    return;
                }

                // retrieve the license from the POSTed data
                $license_key = 'license_' . $extension['store_item_id'];
                $license = sanitize_text_field(trim($_POST[$license_key]));

                // Call the custom API.
                $response = wp_remote_get(\sprintf("https://www.wpo365.com/?edd_action=deactivate_license&license=%s&item_id=%s&url=%s", $license, $extension['store_item_id'], home_url()), array('timeout' => 15, 'sslverify' => false));

                // make sure the response came back okay
                if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {

                    if (is_wp_error($response)) {
                        $message = $response->get_error_message();
                    } else {
                        $message = __('An error occurred, please try again.');
                    }
                }

                $option_name = 'license_' . $extension['store_item_id'];
                $base_url = is_multisite()
                    ? network_admin_url('admin.php?page=wpo365-manage-licenses')
                    : admin_url('admin.php?page=wpo365-manage-licenses');

                if (!empty($message)) {
                    $redirect = add_query_arg(array('sl_activation' => 'false', 'message' => urlencode($message)), $base_url);
                } else {
                    Options_Service::add_update_option($option_name, '');
                    $license_data = json_decode(wp_remote_retrieve_body($response));

                    if ($license_data->license == 'deactivated') {
                        $redirect = add_query_arg(array('sl_activation' => 'true', 'message' => urlencode('License for ' . $extension['store_item'] . ' has been successfully deactivated.')), $base_url);
                    } else {
                        $redirect = add_query_arg(array('sl_activation' => 'true', 'message' => urlencode('License for ' . $extension['store_item'] . ' could not be deactivated. Please try again.')), $base_url);
                    }
                }

                wp_redirect($redirect);
                exit();
            }
        }

        public static function license_page()
        {
            ?>
            <style>
                .wpo365-license-table {
                    background: #ffffff;
                    border: 1px solid #cccccc;
                    box-sizing: border-box;
                    float: left;
                    margin: 0 15px 15px 0;
                    max-width: 350px;
                    min-height: 220px;
                    padding: 14px;
                    position: relative;
                    position: relative;
                    width: 30.5%;
                }

                .wpo365-license-table TH {
                    background-color: #f9f9f9;
                    border-bottom: 1px solid #cccccc;
                    display: block;
                    margin: -14px -14px 20px;
                    padding: 14px;
                    width: 100%;
                }

                .wpo365-license-table TD {
                    display: block;
                    padding: 0;

                }

                .wpo365-license-table TD input[type=text] {
                    margin: 0 0 8px;
                    width: 100%;
                }

                .wpo365-license-table TD DIV {
                    background: #fafafa;
                    border-top: 1px solid #eeeeee;
                    bottom: 14px;
                    box-sizing: border-box;
                    margin: 20px -14px -14px;
                    min-height: 67px;
                    padding: 14px;
                    position: absolute;
                    width: 100%;
                }
            </style>
            <div class="wrap">
                <h2><?php _e('WPO365 | Licenses'); ?></h2>
                <form method="post">
                    <input type="hidden" id="store_item_id" name="store_item_id">
                    <table class="form-table">
                        <tbody>

                            <?php foreach (self::$extensions as $slug => $data) :
                                $license_key = 'license_' . $data['store_item_id'];
                                $license = Options_Service::get_global_string_var($license_key);
                            ?>
                                <tr valign="top" class="wpo365-license-table">
                                    <th scope="row" valign="top">
                                        <?php echo esc_html($data['store_item']) ?>
                                    </th>
                                    <td>
                                        <?php echo wp_nonce_field('wpo365-manage-licenses', 'wpo365_license_nonce'); ?>
                                        <input type="text" class="regular-text" id="<?php echo esc_attr($license_key) ?>" name="<?php echo esc_attr($license_key) ?>" value="<?php echo esc_attr($license) ?>">

                                        <?php if (!empty($license)) : ?>
                                            <input type="submit" class="button-secondary" name="deactivate_license" value="<?php _e('Deactivate License'); ?>" onclick="document.getElementById('store_item_id').value = <?php echo esc_attr($data['store_item_id']) ?>" />
                                        <?php else : ?>
                                            <input type="submit" class="button-secondary" name="activate_license" value="<?php _e('Activate License'); ?>" onclick="document.getElementById('store_item_id').value = <?php echo esc_attr($data['store_item_id']) ?>" />
                                        <?php endif ?>

                                        <div>
                                            <p><a href="https://www.wpo365.com/your-account/" target="_blank">Manage Sites</a></p>
                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach ?>
                        </tbody>
                    </table>
                </form>
    <?php
        }
    }
}
