<?php

if ( !defined( 'ABSPATH' ) )
    exit;

    $loggerMarkup = sprintf('<span class = "button" id="nfsalesforcecrm_clear_debug_logger">%s</></span><br /><span> %s</span><br /><br />',__( 'Clear debug log', 'ninja-forms-salesforce-crm'  ),__( 'Clear logs when debugging is complete and the logger has been turned off.', 'ninja-forms-salesforce-crm'  ));
    $loggerMarkup .= sprintf('<span class = "button" id="nfsalesforcecrm_download_debug_logger">%s</span><br /><span> %s</span>',__( 'Download debug log', 'ninja-forms-salesforce-crm'  ),__( 'Providing log data with support requests helps diagnose issues.', 'ninja-forms-salesforce-crm' ));
        
return apply_filters( 'nf_salesforce_plugin_settings', array(
    /*
      |--------------------------------------------------------------------------
      | Client Key and Secret
      |--------------------------------------------------------------------------
     */

    'nfsalesforcecrm_consumer_key' => array(
        'id' => 'nfsalesforcecrm_consumer_key',
        'type' => 'textbox',
        'label' => __( 'Consumer Key', 'ninja-forms-salesforce-crm' ),
    ),
    'nfsalesforcecrm_consumer_secret' => array(
        'id' => 'nfsalesforcecrm_consumer_secret',
        'type' => 'textbox',
        'label' => __( 'Consumer Secret', 'ninja-forms-salesforce-crm' ),
    ),
    /*
      |--------------------------------------------------------------------------
      | Open Auth - authorization code
      | Retrieved after storing Key and Secret
      |--------------------------------------------------------------------------
     */
    'nfsalesforcecrm_authorization_code_instructions' => array(
        'id' => 'nfsalesforcecrm_authorization_code_instructions',
        'type' => 'html',
        'label' => __( 'Authorization Code Setup', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    'nfsalesforcecrm_authorization_code' => array(
        'id' => 'nfsalesforcecrm_authorization_code',
        'type' => 'textbox',
        'label' => __( 'Authorization Code', 'ninja-forms-salesforce-crm' ),
    ),
    'nfsalesforcecrm_refresh_token_instructions' => array(
        'id' => 'nfsalesforcecrm_refresh_token_instructions',
        'type' => 'html',
        'label' => __( 'Generate Refresh Token', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    /*
     * REFRESH TOKEN, in 3.0 is stored in account data
     * In 2.9, it is stored with settings by using readonly field
     */
    'nfsalesforcecrm_refresh_token' => array(
        'id' => 'nfsalesforcecrm_refresh_token',
        'type' => 'html',
        'label' => __( 'Refresh Token', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    /*
      |--------------------------------------------------------------------------
      | List of Objects to refresh
      |
      |--------------------------------------------------------------------------
     */
    'nfsalesforcecrm_refresh_objects_instructions' => array(
        'id' => 'nfsalesforcecrm_refresh_objects_instructions',
        'type' => 'html',
        'label' => __( 'Click to refresh Objects', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    'nfsalesforcecrm_available_objects' => array(
        'id' => 'nfsalesforcecrm_available_objects',
        'type' => 'textbox',
        'label' => __( 'Objects to Retrieve', 'ninja-forms-salesforce-crm' ),
    ),
    'nfsalesforcecrm_comm_data_status' => array(
        'id' => 'nfsalesforcecrm_comm_data_status',
        'type' => 'html',
        'label' => __( 'Status', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    /*
      |--------------------------------------------------------------------------
      | Advanced commands
      |--------------------------------------------------------------------------
     */
    'nfsalesforcecrm_advanced_codes' => array(
        'id' => 'nfsalesforcecrm_advanced_codes',
        'type' => 'textbox',
        'label' => __( 'Advanced Commands', 'ninja-forms-salesforce-crm' ),
    ),
    /*
      |--------------------------------------------------------------------------
      | Support Functions
      |--------------------------------------------------------------------------
     */
    /**
     * Added 2018-03-28 
     * @since 3.1.0
     */
    'nfsalesforcecrm_ordered_request' => array(
        'id' => 'nfsalesforcecrm_ordered_request',
        'type' => 'html',
        'label' => __( 'Ordered Request', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    /**
     * @since 3.1.0
     */
    'nfsalesforcecrm_field_map_array' => array(
        'id' => 'nfsalesforcecrm_field_map_array',
        'type' => 'html',
        'label' => __( 'Submission Field Map', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    'nfsalesforcecrm_comm_data_debug' => array(
        'id' => 'nfsalesforcecrm_comm_data_debug',
        'type' => 'html',
        'label' => __( 'Debug', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    /*
      |--------------------------------------------------------------------------
      | Salesforce Objects and Fields
      |--------------------------------------------------------------------------
     */
    'nfsalesforcecrm_account_data' => array(
        'id' => 'nfsalesforcecrm_account_data',
        'type' => 'html',
        'label' => __( 'Availalable Salesforce Fields and Objects', 'ninja-forms-salesforce-crm' ),
        'html' => '', // created on construction
    ),
    'nfsalesforcecrm_divider_logger' => array(
        'id'    => 'nfsalesforcecrm_divider_logger',
        'type'  => 'html',
        'label' => '',
        'html' => '<hr />'
    ),
    'nfsalesforcecrm_turn_on_debug_logger' => array(
        'id'    => 'nfsalesforcecrm_turn_on_debug_logger',
        'type'  => 'checkbox',
        'label' =>__( 'Turn on Debug Logger', 'ninja-forms-salesforce-crm'  ),
        'desc'=>sprintf('%s <a href="https://ninjaforms.com/docs/salesforce-crm/">%s</a> ',__('The debug logger records diagnostic data that can be exported and sent to our support team if you suspect there\'s an issue with this add-on. Only active when turned on.', 'ninja-forms-salesforce-crm'),__('Learn more', 'ninja-forms-salesforce-crm')) // Link to document page for plugin
    ), 
    'nfsalesforcecrm_logger_commands' => array(
        'id'    => 'nfsalesforcecrm_logger_commands',
        'type'  => 'html',
        'label' =>__( 'Logger Commands', 'ninja-forms-salesforce-crm'  ),
        'html' => $loggerMarkup,
    ), 
        ) );
