<?php

/**
 * Handles the Process of the Add to Salesforce Action
 *
 * @author stuartlb3
 */
class ProcessAddToSalesforce {

    /**
     * Action Settings from form submission
     * @var array
     */
    protected $action_settings;

    /**
     * Form ID of the submission
     * @var string
     */
    protected $form_id;

    /**
     * Data array of the form submission
     * @var array
     */
    protected $data;

    /**
     * Salesforce fields available for mapping as indexed array of 'name''value' pairs
     *
     * 'name' is reader-friendly, 'value' is programmatic
     *
     * @var array
     */
    protected $field_map_array;

    /**
     * Field mapping keys of submission data to extract
     * @var array
     */
    protected $fields_to_extract;

    /**
     * Lookup array keyed on reader-friendly 'name' and programmatic 'value
     * @var array
     */
    protected $field_map_lookup;

    /**
     * Request array built from field map array and submission data
     *
     * Used by the Request object; structure is indexed array of associative
     * array using FieldsToExtract values as keys.  Built by extracting
     * submission data and replacing the user-friendly field map value with the
     * programmatic one
     *
     * Structure is:
     *  array(
     *      array( 'form_field' => {}, 'field_map' =>{} , 'special_instructions' =>{} ),
     *      . . .
     *  )
     *
     * @var array
     */
    protected $field_map_data;

    /**
     * Salesforce Request Object
     * @var \SalesforceBuildRequest
     */
    protected $request_object;

    /**
     * Array of Salesforce objects in the order to be created
     * @var array
     */
    protected $object_request_list;

    /**
     * Comm data object that receives and stores communication data
     * @var \SalesforceCommData
     */
    protected $comm_data_object;

    /**
     * AnalyzeResponse object to analyze and return pertinent information
     * @var \SalesforceAnalyzeResponse
     */
    protected $analyze_response_object;

    /**
     * Support object to proved contextual support
     * @var \SalesforceContextualSupport
     */
    protected $support_object;

    /**
     * Array of access parameters with the following key-value pairs:
     *  'access_token' =>
     *  'instance' =>
     *  'version_url' =>
     * @var array
     */
    protected $api_parameter_array;

    /**
     * Array of messages from communication
     * @var array
     */
    protected $status_message_array = array();

    /**
     * Processes the Add to Salesforce Action upon submission
     *
     * @param array $action_settings Action Settings from form submission
     * @param string $form_id Form ID of the submission
     * @param array $data  Data array of the form submission
     * @param array $field_map_array Salesforce fields available for mapping as indexed array of 'name''value' pairs
     * @param array $fields_to_extract Field mapping keys of submission data to extract
     */
    public function __construct( $action_settings, $form_id, $data, $field_map_array, $fields_to_extract ) {

        $this->action_settings = $action_settings;

        $this->form_id = $form_id;

        $this->data = $data;

        $this->field_map_array = $field_map_array;

        $this->fields_to_extract = $fields_to_extract;
    }

    /**
     * Processes the Add To Salesforce action
     * @return boolean
     */
    public function processForm() {

        $this->comm_data_object->resetKey( 'status' );
        $this->comm_data_object->resetKey( 'debug' );
        $this->comm_data_object->resetKey( 'field_map_array' );
        $this->comm_data_object->resetKey( 'ordered_request' );

        $this->getAuthorization();

        if ( 'success' != $this->api_parameter_array[ 'result' ] ) {

            return;
        }

        $this->extractFieldData();

        $this->request_object->setFieldArray( $this->field_map_data );

        $this->request_object->iterateFieldArray();

        $this->getObjectRequestList();

        if ( !$this->object_request_list ) {

            return;
        }

        $this->iterateObjectList();

        $this->checkForDuplicates();
    }

    /**
     * Retrieves and sets the API parameter array; false on failure
     *
     * Updates the CommData status with results
     *
     * TODO: add detailed failure analysis on failure
     * ex: missing parameters, no internet
     */
    protected function getAuthorization() {

        $this->api_parameter_array = nfsalesforcecrm_retrieve_api_parameters();

        if ( 'failure' == $this->api_parameter_array[ 'result' ] ) {

            $api_status = $this->api_parameter_array[ 'status' ];

            $message_array = $this->support_object->setAndSearchContext( $api_status, 'Authorization Error', '' );

            $this->appendStatusMessageArray($message_array);

            foreach ( $message_array as $message ) {

                $this->comm_data_object->append( 'status', $message );
            }
        } else {

            $message = __( 'Successful authorization from Salesforce', 'ninja-forms-salesforce-crm' );

            $this->appendStatusMessageArray(array($message));

            $this->comm_data_object->append( 'status', $message );
        }
    }

    /**
     * Retrieves the Object Request List, if empty, logs CommData status message
     */
    protected function getObjectRequestList() {

        $this->object_request_list = $this->request_object->get_object_request_list();

        if ( !$this->object_request_list ) {

            $message = __( 'The object request list is empty', 'ninja-forms-salesforce-crm' );

            $this->comm_data_object->append( 'status', $message );
        }
    }

    /**
     * Iterates the array of objects and inserts them into Salesforce
     * @return boolean
     */
    protected function iterateObjectList() {

        foreach ( $this->object_request_list as $salesforce_object ) {

            $object_field_array = $this->request_object->get_object_field_list( $salesforce_object );

            $this->logOrderedRequest( $salesforce_object, $object_field_array );

//            $temp[] = $object_field_array;
            $new_record_parameter_array = $this->api_parameter_array;
            $new_record_parameter_array[ 'object_name' ] = $salesforce_object;
            $new_record_parameter_array[ 'field_array' ] = $object_field_array;

            $new_object_record = new SalesforcePostNewRecordObject( $new_record_parameter_array );

            $new_record_id = $new_object_record->get_new_record_id();

            if ( $new_record_id ) {
                $this->request_object->link_child_objects( $salesforce_object, $new_record_id );
            }

            $this->logResponses($salesforce_object. ' creation', $new_object_record, 'create');
        }
    }

    /**
     * Gets raw response and analysis and logs them
     * @param string $object_name
     * @param \SalesforceCommunication $comm_object
     */
    protected function logResponses($object_name, $comm_object,$context)
    {
        $temp_array = $comm_object->get_comm_data();

        $this->comm_data_object->append('debug', $temp_array[ 'debug' ]);

        $raw_response = $comm_object->getRawResponse();

        $analysis = $this->analyzeResponse($raw_response,$context);

        $message_array = $this->buildMessageArray($object_name, $analysis);

        $this->appendStatusMessageArray($message_array);

        $this->updateStatus($message_array);
    }

    /**
     * Cycle through the duplicate check array and check if the given
     * Salesforce object and field have more than one of the same user value
     * If true, create a task with description identifying the object,
     * field, and value that is duplicated
     *
     */
    protected function checkForDuplicates()
    {
        $duplicate_check_array = $this->request_object->get_duplicate_check_array();

        if (!$duplicate_check_array) {
            return false;
        }

        foreach ($duplicate_check_array as $salesforce_object => $field_check_array) {

            $duplicate_check_parameter_array = $this->api_parameter_array;
            $duplicate_check_parameter_array[ 'object_name' ] = $salesforce_object;

            /*
             * NOTE: duplicate check is built as an array of arrays so that
             * multiple matches could be added in the future if needed;
             * currently checking only first array
             *
             */
            $duplicate_check_parameter_array[ 'field_name' ] = $field_check_array[ 0 ][ 'salesforce_field' ];
            $duplicate_check_parameter_array[ 'field_value' ] = $field_check_array[ 0 ][ 'user_value' ];

            $duplicate_check_object = new SalesforceDuplicateCheck($duplicate_check_parameter_array);

            $this->logResponses('Duplicate Check', $duplicate_check_object, 'getrecords');

            $response = $duplicate_check_object->get_duplicate_check_response();

            /*
             * If more than one entry is returned, there is a duplicate
             * Create a task
             */

            if (isset($response[ 'totalSize' ]) && 1 < $response[ 'totalSize' ]) {

                $task_to_review_duplicate_request_array = $this->api_parameter_array;
                $task_to_review_duplicate_request_array[ 'object_name' ] = 'Task';
                $task_to_review_duplicate_request_array[ 'field_array' ] = nfsalesforcecrm_build_duplicate_check_task_array($duplicate_check_parameter_array);

                $new_duplicate_task_record = new SalesforcePostNewRecordObject($task_to_review_duplicate_request_array);

                $this->logResponses('Duplicate Task', $new_duplicate_task_record, 'create');
            }
        }
    }

    /**
     * Given a raw response, gets analysis and logs into CommData
     * @param array $response Raw response from CommunicationObject
     * @return array Response analysis
     */
    protected function analyzeResponse( $response, $context ) {

        $this->analyze_response_object->setResponse( $response,$context );

        $this->analyze_response_object->analyzeResponse();

        $analysis = $this->analyze_response_object->getResponseAnalysis();

        return $analysis;
    }

    /**
     * Given an object and the analysis, builds messages with appended support
     * @param string $object Name of Salesforce object
     * @param array $analysis Response analysis from SalesforceAnalyzeResponse
     * @return array Message array
     */
    protected function buildMessageArray( $object, $analysis ) {

        $message_array = array();

        if ( $analysis[ 'success' ] ) {

            $temp = $object . ': ' . __( 'Successfully completed', 'ninja-forms-salesforce-crm' ) . '.  ';

            $message_array[] = $temp;
        } else {

            $temp = $object . ': ' . __( 'NOT successfully completed', 'ninja-forms-salesforce-crm' ) . '.  ';

            $message_array[] = $temp;

            $help_array = $this->appendContextualHelp( $analysis[ 'errors' ] );

            $message_array = array_merge( $message_array, $help_array );
        }

        return $message_array;
    }

    /**
     * Updates status, given an array of messages
     */
    protected function updateStatus( $message_array ) {

        foreach ( $message_array as $message ) {

            $this->comm_data_object->append( 'status', $message );
        }
    }

    protected function appendContextualHelp( $errors ) {

        $help_array = array();

        foreach ( $errors as $error ) {

            $support = $this->support_object->setAndSearchContext( $error[ 'message' ], $error[ 'errorCode' ], $error[ 'field' ] );

            if ( empty( $help_array ) ) {

                $help_array = $support;
            } else {

                $help_array = array_merge( $help_array, $support );
            }
        }

        return $help_array;
    }

        /**
     * Appends status_message_array with given message array
     * @param array $array Messages to append
     */
    protected function appendStatusMessageArray($array){

        if(empty($this->status_message_array)){

            $this->status_message_array = $array;
        }else{

            $this->status_message_array = array_merge($this->status_message_array,$array);
        }
    }

    /**
     * Given the object name and field array, add it Comm Data ordered request
     *
     * @param string $object
     * @param array $field_array
     */
    protected function logOrderedRequest( $object, $field_array ) {

        $this->comm_data_object->append( 'ordered_request', array(
            'object' => $object,
            'field_name' => '',
            'value' => ''
        ) );

        foreach ( $field_array as $key => $value ) {

            $temp = array(
                'object' => '',
                'field_name' => $key,
                'value' => $value,
            );

            $this->comm_data_object->append( 'ordered_request', $temp );
        }
    }

    /**
     * Builds the request array from extracted field mapping data
     */
    protected function extractFieldData() {

        $this->field_map_data = array();  // initialize

        $field_map_data = $this->action_settings[ 'salesforce_field_map' ]; // matches option repeater 'name'

        if ( !is_array( $field_map_data ) ) {

            //TODO: add some contextual help here
            return; // stop if no array
        }

        $this->buildFieldMapLookup();

        foreach ( $field_map_data as $field_data ) {// cycle through each mapped field
            $map_args = array();

            foreach ( $this->fields_to_extract as $field_to_extract ) { // cycle through each column in the repeater
                if ( isset( $field_data[ $field_to_extract ] ) ) {
                    $value = $field_data[ $field_to_extract ];

                    // for the field map, replace the human readable version with the coded version
                    if ( 'field_map' == $field_to_extract ) {

                        $value = $this->field_map_lookup[ $value ];
                    }

                    $map_args[ $field_to_extract ] = $value;
                }
            }

            $this->field_map_data[] = $map_args;
        }

        $this->comm_data_object->set( 'field_map_array', $this->field_map_data );
    }

    /**
     * Builds associative array['name'] => 'value' for available field maps
     *
     * The lookup is keyed on the reader-friendly 'name'  to lookup the mapping value
     */
    protected function buildFieldMapLookup() {

        $useProgrammatic = NF_SalesforceCRM()->advanced_commands->isAdvancedCommandSet('use_programmatic');
        
        $this->field_map_lookup = array(); // initialize

        foreach ( $this->field_map_array as $array ) {
            $mapping = $array[ 'name' ];

            if($useProgrammatic){
                $mapping = $array['value'];
            }
            $this->field_map_lookup[ $mapping] = $array[ 'value' ];
        }
    }

    /**
     * Inject the Salesforce Request Object for processing
     * @param object $object
     */
    public function setRequestObject( $object ) {

        $this->request_object = $object;
    }

    /**
     * Inject the Communication Data Object for managing comm data
     * @param array $object
     */
    public function setCommDataObject( $object ) {

        $this->comm_data_object = $object;
    }

    /**
     * Inject the AnalyzeResponse Object to analyze and return information
     * @param array $object SalesforceAnalyzeResponse class
     */
    public function setAnalyzeResponseObject( $object ) {

        $this->analyze_response_object = $object;
    }

    /**
     * Inject the Support Object to provide contextual support
     * @param array $object NF_SalesforceCRM_Support class
     */
    public function setSupportObject( $object ) {

        $this->support_object = $object;
    }

    /**
     * Returns the $data from the form submission
     * @return array
     */
    public function getData() {

        return $this->data;
    }
/**
 * Returns Action messages, optionally imploded per formate
 *
 * plain = plain text line breaks
 * html, screen = <br />
 *
 * @param string $format
 * @return mixed
 */
    public function getMessages(  ) {

            $messages[ 'status' ] = $this->status_message_array;


        return $messages;
    }

}
