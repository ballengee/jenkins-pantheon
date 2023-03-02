<?php

if (!defined('ABSPATH'))
    exit;

use NinjaForms\Salesforce\Common\Interfaces\NfLogger;
use NinjaForms\Salesforce\Common\Factories\LoggerFactory;
use NinjaForms\Salesforce\Common\Routes\DebugLog as DebugLogRoutes;

/*
 * Plugin Name: Ninja Forms - Salesforce CRM
 * Plugin URI: http://lb3computingsolutions.com
 * Description: Salesforce Extension connecting Ninja Forms to your Salesforce Account
 * Version: 3.3.2
 * Author: Stuart Sequeira
 * Author URI: http://lb3computingsolutions.com/about
 * Text Domain: ninja-forms-salesforce-crm
 *
 * Release Description: Merge branch 'release-3.3.2'
 * Copyright 2016 Saturday Drive
 */

if (version_compare(get_option('ninja_forms_version', '0.0.0'), '3.0.0', '<') || get_option('ninja_forms_load_deprecated', FALSE)) {


    // deprecated folder url
    if (!defined('NF2SALESFORCECRM_PLUGIN_URL')) {
        define('NF2SALESFORCECRM_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    // deprecated folder path
    if (!defined('NF2SALESFORCECRM_PLUGIN_DIR')) {
        define('NF2SALESFORCECRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
    }

    // deprecated root file
    if (!defined('NF2SALESFORCECRM_PLUGIN_FILE')) {
        define('NF2SALESFORCECRM_PLUGIN_FILE', __FILE__);
    }


    if (!defined('NFSALESFORCECRM_MODE')) {
        /**
         * @var string Which NF version is used - 2.9x for before 3
         */
        define('NFSALESFORCECRM_MODE', '2.9x');
    }

    /*
     * Include shared functions
     */
    include 'deprecated/includes/admin/functions-deprecated.php';
    include 'deprecated/ninja-forms-salesforce-crm-deprecated.php';
} else {

    // define Salesforce mode as POST3
    if (!defined('NFSALESFORCECRM_MODE')) {
        /**
         * @var string Which NF version is used - POST3 is for all 3.0+
         */
        define('NFSALESFORCECRM_MODE', 'POST3');
    }

    /*
     * Include shared functions
     */
    include_once 'includes/Admin/Functions.php';
    include_once 'includes/Admin/salesforce-object-refresh.php';
    include_once 'includes/Admin/salesforce-api-parameters.php';
    include_once 'includes/Admin/build-salesforce-field-list.php';

    /**
     * Class NF_SalesforceCRM
     */
    final class NF_SalesforceCRM {

        const VERSION = '3.3.2';
        const SLUG = 'salesforce-crm';
        const NAME = 'Salesforce CRM';
        const AUTHOR = 'Stuart Sequeira';
        const PREFIX = 'NF_SalesforceCRM';

        /**
         * @var string ID of Salesforce settings section for redirects
         */
        const BOOKMARK = 'ninja_forms_metabox_salesforcecrm_settings';

        /**
         * @var NF_SalesforceCRM
         * @since 3.0
         */
        private static $instance;

        /**
         * Plugin Directory
         *
         * @since 3.0
         * @var string $dir
         */
        public static $dir = '';

        /**
         * Plugin URL
         *
         * @since 3.0
         * @var string $url
         */
        public static $url = '';

        /**
         * Route for debug log REST requests
         *
         * @var string
         */
        protected $debugLogSlug = 'salesforce-crm';

        /**
         * Advanced commands object
         * @var NF_SalesforceCRM_Admin_AdvancedCommands
         */
        public $advanced_commands;

        /** @var LoggerFactory */
        protected $loggerFactory;

        /**
         * Main Plugin Instance
         *
         * Insures that only one instance of a plugin class exists in memory at any one
         * time. Also prevents needing to define globals all over the place.
         *
         * @since 3.0
         * @static
         * @static var array $instance
         * @return NF_SalesforceCRM Highlander Instance
         */
        public static function instance() {

            if (!isset(self::$instance) && !(self::$instance instanceof NF_SalesforceCRM)) {
                self::$instance = new NF_SalesforceCRM();

                self::$dir = plugin_dir_path(__FILE__);

                self::$url = plugin_dir_url(__FILE__);

                /*
                 * Register our autoloader
                 */
                spl_autoload_register(array(self::$instance, 'autoloader'));
            }

            return self::$instance;
        }

        public function __construct() {
            /*
             * load the global variables
             * function in Admin/Functions.php
             */
            nfsalesforce_load_globals();

            // Add composer autoloader
            $this->composerAutoloader();

            /*
             * Set up Licensing
             */
            add_action('admin_init', array($this, 'setup_license'));

            /*
             * Create Admin settings
             */
            add_action('ninja_forms_loaded', array($this, 'setup_admin'));

            /*
             * Load Classes
             */
            add_action('ninja_forms_loaded', array($this, 'load_classes'));
            add_action('plugins_loaded', array($this, 'initializeLogger'));
            

            /* Adds tools accessed from main instance */
            add_action('plugins_loaded', function() {
                $this->loadTools();
            }, 4);

            /* adds initial filters */
            add_action('init', array($this,'initiateFilters'));            
            
            add_action('init', [$this, 'registerSettingsScript']);
            /*
             * Register Actions
             */
            add_filter('ninja_forms_register_actions', array($this, 'register_actions'));
        }

        /**
         * Load an autoloader from vendor subdirectory
         *
         * This function can be copied and reused in other plugins using composer's
         * PSR-4 specification because it is namespaced within this file to avoid
         * collision.
         *
         * @return boolean
         */
        function composerAutoloader(): bool
        {
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';

            if (file_exists($autoloader)) {
                include_once $autoloader;
                $return = true;
            } else {
                $return = false;
            }
            return $return;
        }

        /**
         * Initializes debug logger for injection
         */
        public function initializeLogger(): void
        {
            if (class_exists('Ninja_Forms')) {
                $debugLog = \Ninja_Forms()->get_setting('nfsalesforcecrm_turn_on_debug_logger');
            } else {
                $debugLog = false;
            }

            $isDebugOn = (bool)$debugLog;

            $this->loggerFactory = new LoggerFactory($this->debugLogSlug, $isDebugOn);

            $this->loggerFactory->createDebugLogRoutes($this->debugLogSlug);

            $this->injectLogger();

            if ($isDebugOn) {

                //Register any notices we need to apply.
                add_filter('ninja_forms_admin_notices', array($this, 'adminNotices'));
            }
        }

        /**
         * Returns log handler
         *
         * @return NfLogger
         */
        public function logger( ): NfLogger
        {
            return $this->loggerFactory->getLogger();
        }
        /**
         * Inject logger into previously instantiated objects
         *
         * The logger waits until plugins_loaded to check for NF core loading.  Some
         * objects may have been instantiated before this action has fired and
         * cannot be constructed with the logger.  These classes have the logger
         * injected immediately after the logger has been constructed.
         */
        protected function injectLogger(): void
        {

        }

        /**
         * Function to register any admin notices we need to show.
         * 
         * @param $notices (Array) The list of admin notices.
         * @return array The updated list of admin notices.
         * 
         */
        public function adminNotices($notices)
        {
            // Register an admin notice.
            $notices['nfsalesforcecrm_turn_on_debug_logger'] = array(
                'title' => __('Ninja Forms Salesforce Debug Logger', 'ninja-forms-salesforce-crm'),
                'msg' => sprintf(__('%sThe debug logger records data to help solve issues, but should be turned off as soon as you capture enough information.%s', 'ninja-forms-salesforce-crm'), '<p>', '</p>'),
                'int' => 0,
                'ignore_spam' => true,
                'dismiss' => 1
            );

            return $notices;
        }

        public function registerSettingsScript(): void
        {
            $handle = 'salesforce_nfpluginsettings';

            $scriptUrl = self::$url . 'assets/js/nfpluginsettings.js';

            $objectName = $handle;

            $localizedArray = [
                'clearLogRestUrl' => \rest_url() . $this->debugLogSlug . '/' . DebugLogRoutes::DELETELOGSENDPOINT,
                'clearLogButtonId' => 'nfsalesforcecrm_clear_debug_logger',
                'downloadLogRestUrl' => \rest_url() . $this->debugLogSlug . '/' . DebugLogRoutes::GETLOGSENDPOINT,
                'downloadLogButtonId' => 'nfsalesforcecrm_download_debug_logger',

            ];

            //Register asset 
            \wp_enqueue_script(
                $handle,
                $scriptUrl,
                ['jquery'],
                self::VERSION
            );


            \wp_localize_script(
                $handle,
                $objectName,
                $localizedArray
            );
        }
    

        /**
         * Instantiates advanced commands object as public property
         */
        public function loadTools()
        {
            $settings_key = 'nfsalesforcecrm_advanced_codes';

            $this->advanced_commands = new NF_SalesforceCRM_Admin_AdvancedCommands($settings_key);
        }
        
        public function register_actions($actions) {

            // key needs to match $_name property from action
            $actions['addtosalesforce'] = new NF_SalesforceCRM_Actions_AddToSalesforce();

            return $actions;
        }

        /**
         * Adds filters for immediate use
         * 
         * Ensures filters that are called on page load can have values set
         */
        public function initiateFilters()
        {
            add_filter('nfsalesforcecrm_set_connection_type', array( $this, 'returnConnectionType' ), 5);
        }

        /**
         * Checks advanced command "sandbox_", returns connection type
         * 
         * Used for connecting to a sandbox version of the site
         * 
         * @param string $original
         * @return string
         */
        public function returnConnectiontype($original)
        {
            $this->advanced_commands->refreshSettings();
            
            $advanced_command = $this->advanced_commands->variableAdvancedCommand('sandbox_');

            if ($advanced_command) {

               $connection_type = $advanced_command;
            }else{
                
                $connection_type = $original;
            }   
            
            return $connection_type;
        }
              
        /*
         * Set up the licensing
         */

        public function setup_license() {

            if (!class_exists('NF_Extension_Updater'))
                return;

            new NF_Extension_Updater(self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG);
        }

        /**
         * Create the settings page
         */
        public function setup_admin() {

            if (!is_admin())
                return;

            new NF_SalesforceCRM_Admin_Settings();
        }

        public function load_classes() {

            NF_SalesforceCRM::file_include('Comm', 'class-salesforce-build-request');
            NF_SalesforceCRM::file_include('Comm', 'class-salesforce-communication');

            NF_SalesforceCRM::file_include('Comm/authentication', 'class-salesforce-security-credentials');
            NF_SalesforceCRM::file_include('Comm/authentication', 'class-salesforce-get-refresh-token');
            NF_SalesforceCRM::file_include('Comm/authentication', 'class-salesforce-access-token');
            NF_SalesforceCRM::file_include('Comm/authentication', 'class-salesforce-version');


            NF_SalesforceCRM::file_include('Comm/request', 'class-salesforce-describe-object');
            NF_SalesforceCRM::file_include('Comm/request', 'class-salesforce-list-of-objects');
            NF_SalesforceCRM::file_include('Comm/request', 'class-salesforce-post-new-record');
            NF_SalesforceCRM::file_include('Comm/request', 'class-salesforce-check-for-duplicate');
            
            NF_SalesforceCRM::file_include('Classes', 'SalesforceSettingsMarkup');
        }

        /**
         * Returns a configuration specified in a given Config file
         * @param string $file_name
         * @return mixed
         */
        public static function config($file_name) {

            return include self::$dir . 'includes/Config/' . $file_name . '.php';
        }

        /**
         * Includes a specific file in an Includes directory
         * 
         * @param string $sub_dir
         * @param string $file_name
         */
        public static function file_include($sub_dir, $file_name) {

            if(file_exists(self::$dir . 'includes/' . $sub_dir . '/' . $file_name . '.php')){
                include_once self::$dir . 'includes/' . $sub_dir . '/' . $file_name . '.php';
            }
        }

        /**
         * Creates a template for display
         * 
         * @param string $file_name
         * @param array $data
         * @return mixed
         */
        public static function template($file_name = '', array $data = array()) {

            if (!$file_name) {
                return;
            }
            extract($data);

            include self::$dir . 'includes/Templates/' . $file_name;
        }

        /*
         * Optional methods for convenience.
         */

        public function autoloader($class_name) {

            if (class_exists($class_name))
                return;

            if (false === strpos($class_name, self::PREFIX))
                return;

            $class_name = str_replace(self::PREFIX, '', $class_name);
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

            if (file_exists($classes_dir . $class_file)) {
                require_once $classes_dir . $class_file;
            }
        }

        /**
         * 
         * @return array Array of the Account data
         */
        public function get_nfsalesforcecrm_account_data() {

            $data = get_option('nfsalesforcecrm_account_data');

            return $data;
        }

        /**
         * 
         * @return array Array of the communication data
         */
        public function get_nfsalesforcecrm_comm_data() {

            $data = get_option('nfsalesforcecrm_comm_data');

            return $data;
        }

        /**
         * Modify the comm data global
         * 
         * This doesn't write to the database to minimize db calls.  Rather,
         * use update_nfsalesforcecrm_comm_data to write to the db.  If there 
         * is a point where error can halt or branch; run an update to store
         * the last known data.
         * 
         * @param string $key Key of the comm data to update
         * @param string $value Value to update in comm data
         * @param bool $append Add to nested array to preserve previous data
         */
        public function modify_nfsalesforcecrm_comm_data($key = '', $value = '', $append = false) {

            if (0 < strlen($key) || 0 < strlen($value)) {
//                return;
            }

            if ($append) {
                $count = count($this->nfsalesforcecrm_comm_data[$key]);

                if (3 < $count) {

                    array_shift($this->nfsalesforcecrm_comm_data[$key]);
                }

                $this->nfsalesforcecrm_comm_data[$key][] = $value;
            } else {

                $this->nfsalesforcecrm_comm_data[$key] = $value;
            }
        }

        /**
         * Write the current global comm data to the database
         */
        public function update_nfsalesforcecrm_comm_data() {

            update_option('nfsalesforcecrm_comm_data', $this->nfsalesforcecrm_comm_data);
        }

    }

    /**
     * The main function responsible for returning The Highlander Plugin
     * Instance to functions everywhere.
     *
     * Use this function like you would a global variable, except without needing
     * to declare the global.
     *
     * @since 3.0
     * @return {class} Highlander Instance
     */
    function NF_SalesforceCRM() {
        return NF_SalesforceCRM::instance();
    }

    NF_SalesforceCRM();
}


add_filter('ninja_forms_upgrade_settings', 'NF_SalesforceCRM_Upgrade');

function NF_SalesforceCRM_Upgrade($data) {

    /*
     * Sitewide settings
     */
    $plugin_settings = get_option('nfsalesforcecrm_settings', array(
        'nfsalesforcecrm_consumer_key' => '',
        'nfsalesforcecrm_consumer_secret' => '',
        'nfsalesforcecrm_authorization_code' => '',
//	'nfsalesforcecrm_refresh_token'=>'', DO NOT IMPORT, still stored in nfsalesforcecrm_settings
//	'nfsalesforcecrm_refresh_salesforce_objects'=>'', // DO NOT IMPORT, replaced with listener
        'nfsalesforcecrm_available_objects' => '',
            )
    );

    Ninja_Forms()->update_settings(array(
        'nfsalesforcecrm_consumer_key' => $plugin_settings['nfsalesforcecrm_consumer_key'],
        'nfsalesforcecrm_consumer_secret' => $plugin_settings['nfsalesforcecrm_consumer_secret'],
        'nfsalesforcecrm_authorization_code' => $plugin_settings['nfsalesforcecrm_authorization_code'],
        'nfsalesforcecrm_available_objects' => $plugin_settings['nfsalesforcecrm_available_objects'],
            )
    );

    /*
     * Form settings
     */

    // Convert form settings to action.
    if (isset($data['settings']['nfsalesforcecrm_send_to_salesforce']) && 1 == $data['settings']['nfsalesforcecrm_send_to_salesforce']) {

        $new_action = array(
            'type' => 'addtosalesforce',
            'label' => __('Add to Salesforce', 'ninja-forms-salesforce-crm'),
        );

        $field_lookup_array = nfsalesforcecrm_build_field_lookup_array();
        
//        update_option('data_fields', $data['fields']);  // debug only
        foreach ($data['fields'] as $key => $field) {

            if (!isset($field['data']['nfsalesforcecrm_field_map']) || 'none' == $field['data']['nfsalesforcecrm_field_map']) {
                // this field does not have an insightly field map
                continue;
            }

            if(isset($field_lookup_array[$field['data']['nfsalesforcecrm_field_map']])){
                
                $field_map = $field_lookup_array[$field['data']['nfsalesforcecrm_field_map']];
            }else{
                continue;
            }
            
            $special_instructions = 'none'; // set default for special instructions

            if (isset($field['data']['nfsalesforcecrm_duplicate_check']) && '1' === $field['data']['nfsalesforcecrm_duplicate_check']) {

                $special_instructions = 'DuplicateCheck';
            }

            if (isset($field['data']['nfsalesforcecrm_date_interval']) && '1' === $field['data']['nfsalesforcecrm_date_interval']) {

                $special_instructions = 'DateInterval';
            }

            if (isset($field['data']['nfsalesforcecrm_date_format']) && '1' === $field['data']['nfsalesforcecrm_date_format']) {

                $special_instructions = 'DateFormat';
            }
   
            if (isset($field['type']) && '_upload' === $field['type']) {

                $special_instructions = 'FileUpload';
            }
            $new_action['salesforce_field_map'][] = array(// the name from ActionFieldMapSettings
                'form_field' => '{field:' . $field['id'] . '}',
                'field_map' => $field_map,
                'special_instructions' => $special_instructions,
            );
        }

        $data['actions'][] = $new_action;
    }
// update_option('returned_data',$data);   
    return $data;
}

/**
 * 
 * @return array A lookup array keyed on the stored value to replace with the proper label
 */
function nfsalesforcecrm_build_field_lookup_array(){
    
    $lookup_array = array(); // initialize
    
    $field_list = nfsalesforcecrm_build_salesforce_field_list_v2_9();
    
    foreach($field_list as $field){
        
        $lookup_array[$field['value']]=$field['name'];
    }
    
    return $lookup_array;
}
