<?php

/**
 *  Plugin Name: WPO365 | LOGIN
 *  Plugin URI: https://wordpress.org/plugins/wpo365-login
 *  Description: With WPO365 | LOGIN users can sign in with their corporate or school (Azure AD / Microsoft Office 365) account to access your WordPress website: No username or password required (OIDC or SAML 2.0 based SSO). Plus you can send email using Microsoft Graph instead of SMTP from your WordPress website.
 *  Version: 19.4
 *  Author: support@wpo365.com
 *  Author URI: https://www.wpo365.com
 *  License: GPL2+
 */

namespace Wpo;

require __DIR__ . '/vendor/autoload.php';

use \Wpo\Core\Globals;
use \Wpo\Core\Wp_Hooks;

use \Wpo\Services\Dependency_Service;
use \Wpo\Services\Files_Service;
use \Wpo\Services\Request_Service;
use \Wpo\Services\Router_Service;
use Wpo\Services\Options_Service;

// Prevent public access to this script
defined('ABSPATH') or die();

if (!class_exists('\Wpo\Login')) {

    class Login
    {

        private $dependencies;

        public function __construct()
        {
            $this->deactivation_hooks();
            add_action('plugins_loaded', array($this, 'init'), 1);
        }

        public function init()
        {
            $skip_init = (defined('WP_CLI') && constant('WP_CLI') === true) || (defined('WPO_AUTH_SCENARIO') && constant('WPO_AUTH_SCENARIO') == 'internet' && !\is_admin());

            if ($skip_init) {
                add_action('login_init', array($this, 'load'), 1);
                return;
            }

            $this->load();
        }

        public function load()
        {
            Globals::set_global_vars(__FILE__, __DIR__);
            load_plugin_textdomain('wpo365-login', false, dirname(plugin_basename(__FILE__)) . '/languages');
            $this->cache_dependencies();
            Wp_Hooks::add_wp_hooks();
            $this->load_gutenberg_blocks();

            $has_route = Router_Service::has_route();

            if (!Options_Service::get_global_boolean_var('no_sso', false) && !$has_route) {
                add_action('init', '\Wpo\Services\Authentication_Service::authenticate_request', 1);
            }
        }

        /**
         * @since 13.0
         * 
         * To load the Gutenberg M365 blocks.
         */
        private function load_gutenberg_blocks()
        {
            $apps = array(
                'docs' => array(
                    'edition' => 'basic',
                    'load_front_end_assets' => true,
                )
            );


            $plugins_dir = __DIR__;
            $plugins_url = \plugins_url() . '/' . basename(__DIR__);

            foreach ($apps as $app => $settings) {
                new \Wpo\Blocks\Loader($app, $settings['edition'], $plugins_dir, $plugins_url, $settings['load_front_end_assets']);
            }
        }

        private function cache_dependencies()
        {
            $this->dependencies = Dependency_Service::get_instance();
            $this->dependencies->add('Request_Service', Request_Service::get_instance(true));
            $this->dependencies->add('Files_Service', Files_Service::get_instance());
        }

        private function deactivation_hooks()
        {

            if (\class_exists('\Wpo\Sync\Sync_Manager')) {
                // Delete possible cron jobs
                register_deactivation_hook(__FILE__, function () {
                    \Wpo\Sync\Sync_Manager::get_scheduled_events(true);
                });
            }

            if (\class_exists('\Wpo\Sync\SyncV2_Service')) {
                // Delete possible cron jobs
                register_deactivation_hook(__FILE__, function () {
                    \Wpo\Sync\SyncV2_Service::get_scheduled_events(null, true);
                });
            }
        }
    }
}

$wpo365_login = new Login();
