<?php

if ( !defined( 'ABSPATH' ) || !class_exists( 'NF_Abstracts_Action' ) )
    exit;

/**
 * Create templates, create and save settings; calls Process object
 */
final class NF_SalesforceCRM_Actions_AddToSalesforce extends NF_Abstracts_Action {

    /**
     * @var string
     */
    protected $_name = 'addtosalesforce'; // child CRM

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = '10';

    /**
     * Salesforce fields for mapping as indexed array of 'name''value' pairs
     *
     * The 'name' is reader-friendly, the 'value' is programmatic
     * @var array
     */
    protected $field_map_array;

    /**
     * Field mapping keys of submission data to extract
     * @var array
     */
    protected $fields_to_extract;

    /**
     * The lookup array built in shared functions, used for dropdown array
     * @var array
     */
    protected $field_map_lookup;

    /**
     * Processing object that initiates requests and handles responses
     * @var object
     */
    protected $form_process;

    /**
     * Communication Data object logs data stored from processing
     * @var object
     */
    protected $comm_data;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->_nicename = __( 'Add To Salesforce', 'ninja-forms' );

        // build the dropdown array
        $this->field_map_array = nfsalesforcecrm_build_salesforce_field_list();

        add_action( 'admin_init', array( $this, 'init_settings' ) );
        add_action( 'ninja_forms_builder_templates', array( $this, 'builder_templates' ) );
        add_filter( 'ninja_forms_run_action_settings', array( $this, 'replace_placeholders' ), 10, 4 );

        add_action( 'nf_admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
    }

    public function enqueueAdminScripts( $data ) {

        wp_enqueue_script( 'nf-salesforce-metadata', NF_SalesforceCRM::$url . 'assets/js/metadata.js', array( 'nf-builder' ) );
    }

    public function save( $action_settings ) {

        $keyed_action_settings = $action_settings[ 'salesforce_field_map' ];

        // ensure field map is array and is not empty before modifying
        if ( !is_array( $keyed_action_settings ) || empty( $keyed_action_settings ) ) {

            return $action_settings;
        }

        $scrubbed_field_map = $this->scrub_action_settings( $keyed_action_settings );

        $action_settings[ 'salesforce_field_map' ] = $scrubbed_field_map;

        return $action_settings;
    }

    public function process( $action_settings, $form_id, $data ) {

        NF_SalesforceCRM::file_include( 'Classes', 'ProcessAddToSalesforce' );

        $this->instantiateFormProcessObject( $action_settings, $form_id, $data );

        $this->injectRequestObject();

        $this->injectCommDataObject();

        $this->injectAnalyzeResponse();

        $this->injectSupportObject();

        $this->form_process->processForm();

        $this->comm_data->storeCommData( 'nfsalesforcecrm_comm_data' );

        $this->action_messages = $this->form_process->getMessages();

        $post_process_data = $this->form_process->getData();

        return $post_process_data;
    }

    protected function retrieveActionMessages() {

        $this->action_messages[ 'screen' ] = $this->form_process->getMessages( 'screen' );
    }

    /**
     * Instantiates the FormProcess object for processing
     */
    protected function instantiateFormProcessObject( $action_settings, $form_id, $data ) {

        $this->form_process = new ProcessAddToSalesforce(
                $action_settings, $form_id, $data, $this->field_map_array, $this->fields_to_extract );
    }

    /**
     * Injects RequestObject into FormProcess object
     *
     * If  extending Request Object exists, injects that instead of standard
     */
    protected function injectRequestObject() {

        if ( class_exists( 'SalesforcePlusBuildRequest' ) ) {

            $request_object = new SalesforcePlusBuildRequest( );
        } else {

            $request_object = new SalesforceBuildRequest( );
        }

        $this->form_process->setRequestObject( $request_object );
    }

    /**
     * Includes, initializes, and injects CommData object into FormProcess object
     */
    protected function injectCommDataObject() {

        NF_SalesforceCRM::file_include( 'Classes', 'SalesforceCommData' );

        $this->comm_data = new SalesforceCommData( );

        $this->comm_data->initializeCommData( 'nfsalesforcecrm_comm_data' );

        $this->form_process->setCommDataObject( $this->comm_data );
    }

    /**
     * Injects AnalyzeResponse object into FormProcess object
     */
    protected function injectAnalyzeResponse() {

        NF_SalesforceCRM::file_include( 'Classes', 'SalesforceAnalyzeResponse' );

        $object = new SalesforceAnalyzeResponse( );

        $this->form_process->setAnalyzeResponseObject( $object );
    }

    /**
     * Injects NF_SalesforceCRM_Support object into FormProcess object
     */
    protected function injectSupportObject() {

        NF_SalesforceCRM::file_include( 'Classes', 'SalesforceContextualSupport' );

        $object = new SalesforceContextualSupport( );

        $this->form_process->setSupportObject( $object );
    }

    public function builder_templates() {
        NF_SalesforceCRM::template( 'custom-field-map-row.html' );
    }

    public function init_settings() {

        $settings = NF_SalesforceCRM::config( 'ActionFieldMapSettings' );

        $this->_settings = array_merge( $this->_settings, $settings );

        $field_dropdown = $this->build_field_map_dropdown( $this->field_map_array );

        $this->_settings[ 'field_map' ][ 'columns' ][ 'field_map' ][ 'options' ] = $field_dropdown;

        $special_instructions = NF_SalesforceCRM::config( 'SpecialInstructions' );
        $this->_settings[ 'field_map' ][ 'columns' ][ 'special_instructions' ][ 'options' ] = $special_instructions;

        $this->fields_to_extract = NF_SalesforceCRM::config( 'FieldsToExtract' );
    }

    /**
     * Build the array of each field to be sent
     *
     * Uses the reader-friendly name for both label and value.  Processing
     * can look up the programmatic value for mapping the request
     * @param type $field_map_array
     * @return array
     */
    protected function build_field_map_dropdown( $field_map_array ) {

        $useProgrammatic = NF_SalesforceCRM()->advanced_commands->isAdvancedCommandSet('use_programmatic');

        $dropdown_array = array();

        foreach ( $field_map_array as $array ) {

            $mapping = $array[ 'name' ];

            if($useProgrammatic){
                $mapping = $array['value'];
            }

            $dropdown_array[] = array(
                'label' => $mapping,
                'value' => $mapping,
            );
        }

        return $dropdown_array;
    }

    /**
     * Remove unused dropdown options stored in specific action settings key
     * @param type $keyed_action_settings
     */
    protected function scrub_action_settings( $keyed_action_settings ) {

        foreach ( $keyed_action_settings as &$field_map_entry ) {

            $field_map_entry[ 'options' ][ 'field_map' ] = array();
            $field_map_entry[ 'settingModel' ][ 'columns' ][ 'field_map' ][ 'options' ] = array();

            $field_map_entry[ 'options' ][ 'special_instructions' ] = array();
            $field_map_entry[ 'settingModel' ][ 'columns' ][ 'special_instructions' ][ 'options' ] = array();
        }

        return $keyed_action_settings;
    }

    /**
     * Replace placeholder tags with Action-generated text
     *
     * $this->action_messages is a keyed collection of messages accrued during
     * the action process; these messages will replace a given placeholder text
     * in other actions, such as the success or email messages
     *
     * @param array $action_settings
     * @param string $form_id
     * @param string $action_id
     * @param array $form_settings
     * @return array
     */
    public function replace_placeholders( $action_settings, $form_id, $action_id, $form_settings ) {

        if ( is_null( $this->form_process ) || '1' != $action_settings[ 'active' ] ) {

            return $action_settings;
        }

        $action_keys = array(
            'success_msg' => '<br />',
            'email_message' => '<br />',
            'email_message_plain' => "\r\n",
        );

        $lookup_array = array( '{salesforce_status}' => 'status' );

        $temp_action_settings = $action_settings;

        foreach ( $action_keys as $action_to_update => $delimiter ) {

            foreach ( $lookup_array as $replace_me => $replacement_key ) {

                $temp_action_settings[ $action_to_update ] = str_replace(
                        $replace_me, implode( $delimiter, $this->action_messages[ $replacement_key ] ), $temp_action_settings[ $action_to_update ]
                );
            }
        }

        $action_settings = $temp_action_settings;

        return $action_settings;
    }

}
