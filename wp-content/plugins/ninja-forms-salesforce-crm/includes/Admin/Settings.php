<?php

if ( !defined( 'ABSPATH' ) )
    exit;

/**
 * Configures the plugin settings and formats support data
 * 
 * Uses shared functions from Functions.php
 */
final class NF_SalesforceCRM_Admin_Settings {

    /**
     * Communication data stored as associative array in WP options table
     * @var array 
     */
    protected $comm_data;

    /**
     * URL for requesting the authorization code
     * @var string 
     */
    protected $auth_code_url_link;

    /**
     * Includes both automatically configured and constructued HTML
     * 
     * Settings are stored using native NF methods.  HTML is retrieved and
     * marked up as needed for display
     * 
     * @var array Settings and html displayed in Salesforce Settings section
     */
    protected $configured_settings;

    /**
     *
     * @var array Stored Salesforce settings retrieved from global variable
     * 
     * Global variable is used b/c it is common with 2.9x and downstream classes
     * rely on it
     */
    protected $salesforce_settings;

    /**
     *
     * @var array Account data of Version, Objects, and Fields
     */
    protected $account_data;

    public function __construct() {

        global $nfsalesforcecrm_settings; // bring in global settings shared with 2.

        $this->salesforce_settings = $nfsalesforcecrm_settings;

        $this->initiliazeCommData();

        $this->account_data = nfsalesforcrcrm_output_account_data();

        add_filter( 'ninja_forms_plugin_settings', array( $this, 'plugin_settings' ), 10, 1 );

        add_filter( 'ninja_forms_plugin_settings_groups', array( $this, 'plugin_settings_groups' ), 10, 1 );
    }

    public function plugin_settings( $settings ) {

        $this->configured_settings = NF_SalesforceCRM()->config( 'PluginSettings' );   
        
        // Moved this method call from __construct so filters can be added to 
        // theme function.php
        // previously, filter had to be in plugin since plugins load before themes
        $this->buildAuthURL();
        
        $this->setHTMLMarkup();

        $advanced_codes = nfsalesforcecrm_extract_advanced_codes();

        $this->optionDisplaySupport( $advanced_codes );

        $this->optionHideSetup( $advanced_codes );

        $settings[ 'salesforcecrm' ] = $this->configured_settings;

        return $settings;
    }

    /**
     * Retrieves the stored Comm Data and sets default for any missing values
     */
    protected function initiliazeCommData() {

        $init_array = array(
            'debug' => array(),
            'ordered_request' => array(),
            'field_map_array' => array(),
        );


        $stored_comm_data = NF_SalesforceCRM()->get_nfsalesforcecrm_comm_data();

        if ( is_array( $stored_comm_data ) ) {

            $this->comm_data = array_merge( $init_array, $stored_comm_data );
        } else {

            $this->comm_data = $init_array;
        }
    }

    /**
     * Sets and marks up the HTML values on the CRM section of settings page
     */
    protected function setHTMLMarkup() {

        $this->configured_settings[ 'nfsalesforcecrm_authorization_code_instructions' ][ 'html' ] = $this->buildAuthorizationCodeMarkup();

        $this->configured_settings[ 'nfsalesforcecrm_refresh_token_instructions' ][ 'html' ] = $this->buildRefreshTokenInstructionsMarkup();

        $this->configured_settings[ 'nfsalesforcecrm_refresh_objects_instructions' ][ 'html' ] = $this->buildRefreshObjectsInstructionsMarkup();

        $this->configured_settings[ 'nfsalesforcecrm_comm_data_status' ][ 'html' ] = $this->buildStatusMarkup();

        $this->configured_settings[ 'nfsalesforcecrm_comm_data_debug' ][ 'html' ] = SalesforceSettingsMarkup::markup( 'comm_data_debug', $this->comm_data[ 'debug' ] );

        $this->configured_settings[ 'nfsalesforcecrm_ordered_request' ][ 'html' ] = SalesforceSettingsMarkup::markup( 'ordered_request', $this->comm_data[ 'ordered_request' ] );
        
        $this->configured_settings[ 'nfsalesforcecrm_field_map_array' ][ 'html' ] = SalesforceSettingsMarkup::markup( 'field_map_array', $this->comm_data[ 'field_map_array' ] );

        $this->configured_settings[ 'nfsalesforcecrm_refresh_token' ][ 'html' ] = $this->salesforce_settings[ 'nfsalesforcecrm_refresh_token' ];

        $this->configured_settings[ 'nfsalesforcecrm_account_data' ][ 'html' ] = $this->account_data;
    }

    /**
     * Optionally display the support settings
     * 
     * @param array $advanced_codes Advanced codes array
     */
    protected function optionDisplaySupport( $advanced_codes ) {

        $support_mode_code = 'support';

        if ( !in_array( $support_mode_code, $advanced_codes ) ) {

            $support_mode_settings = array(
                'nfsalesforcecrm_ordered_request',
                'nfsalesforcecrm_field_map_array',
                'nfsalesforcecrm_comm_data_debug',
            );

            foreach ( $support_mode_settings as $setting ) {

                unset( $this->configured_settings[ $setting ] );
            }
        }
    }

    /**
     * Optionally hide the setup settings
     * 
     * Used to remove the setup settings (to reduce clutter on the page)
     * 
     * @param array $advanced_codes Advanced codes array
     */
    protected function optionHideSetup( $advanced_codes ) {

        $hide_setup_code = 'hide_setup';

        if ( in_array( $hide_setup_code, $advanced_codes ) ) {

            $setup_settings_array = array(
                'nfsalesforcecrm_consumer_key',
                'nfsalesforcecrm_consumer_secret',
                'nfsalesforcecrm_authorization_code_instructions',
                'nfsalesforcecrm_authorization_code',
                'nfsalesforcecrm_refresh_token_instructions',
                'nfsalesforcecrm_refresh_token',
                'nfsalesforcecrm_refresh_objects_instructions',
                'nfsalesforcecrm_available_objects',
                'nfsalesforcecrm_account_data'
            );

            foreach ( $setup_settings_array as $setting ) {

                unset( $this->configured_settings[ $setting ] );
            }
        }
    }

    public function plugin_settings_groups( $groups ) {

        $groups = array_merge( $groups, NF_SalesforceCRM()->config( 'PluginSettingsGroups' ) );
        return $groups;
    }

    /**
     * Build the Authorization URL
     */
    protected function buildAuthURL() {

        $nfsalesforcecrm_connection = apply_filters( 'nfsalesforcecrm_set_connection_type', 'login' );

        $this->auth_code_url_link = 'https://' . $nfsalesforcecrm_connection . '.salesforce.com/services/oauth2/authorize?response_type=code&client_id=';

        $this->auth_code_url_link .= Ninja_Forms()->get_setting( 'nfsalesforcecrm_consumer_key' );

        $this->auth_code_url_link .= '&redirect_uri=https://' . $nfsalesforcecrm_connection . '.salesforce.com/services/oauth2/success';
    }

    /**
     * Used to provide current status of API connection
     */
    protected function buildAuthorizationCodeMarkup() {

        $markup = ''; //initialize

        $markup .= __( 'Enter your Consumer Key and Secret and SAVE your settings before the next step.', 'ninja-forms-salesforce-crm' );

        $markup .= '<br />';

        $markup .= '<span><a href="' . $this->auth_code_url_link . '" target="_blank">Click to generate open authorization code</a></span>';

        $markup .= '<br />';

        $markup .= __( 'Copy the code from the Salesforce response and SAVE it in the Authorization Code box.', 'ninja-forms-salesforce-crm' );

        return $markup;
    }

    /**
     * @return string HTMl markup of listener link for refresh token generation
     */
    protected function buildRefreshTokenInstructionsMarkup() {

        $markup = '';  // initialize

        $refresh_token_link = $this->buildRefreshTokenListenerLink();

        $markup .= '<span><a href="' . $refresh_token_link . '" target="_self">Click to generate refresh token</a></span>';

        return $markup;
    }

    /**
     * @return string HTML markup of the status array
     */
    protected function buildStatusMarkup() {

        if(!isset($this->comm_data[ 'status' ] )){
            
            $markup = ''; // initialize
        }elseif ( !is_array( $this->comm_data[ 'status' ] ) || empty( $this->comm_data[ 'status' ] ) ) {
            
            $markup = htmlentities( $this->comm_data[ 'status' ] );
        } else {

            $markup = implode('<br />', $this->comm_data['status']);
        }

        return $markup;
    }

    /**
     * Builds URL for refresh token listener
     * 
     * @return string Listener URL for refresh token
     * 
     * Needs to match the URL in nfsalesforcecrm_listener
     * 
     */
    protected function buildRefreshTokenListenerLink() {

        $link = ''; //initialize

        $link .= home_url();
        $link .= '?nfsalesforcecrm_instructions=refresh_token';

        return $link;
    }

    /**
     * @return string Markup with link for refreshing object
     */
    protected function buildRefreshObjectsInstructionsMarkup() {

        $markup = '';  // initialize

        $refresh_objects_link = $this->buildRefreshObjectsListenerLink();

        $markup .= '<span><a href="' . $refresh_objects_link . '" target="_self">Click to retrieve your objects and fields</a></span>';

        $markup .= '<br />';

        $markup .= __( 'Enter Salesforce objects that you wish to use in your forms in the Objects to Retrieve box and click \'Refresh Objects\' to make them available in your forms.', 'ninja-forms-salesforce-crm' );

        return $markup;
    }

    /**
     * Builds URL for refresh objects listener
     * 
     * @return string Listener URL for objects token
     * 
     * Needs to match the URL in nfsalesforcecrm_listener
     * 
     */
    protected function buildRefreshObjectsListenerLink() {

        $link = ''; //initialize

        $link .= home_url();
        $link .= '?nfsalesforcecrm_instructions=refresh_objects';

        return $link;
    }

}
