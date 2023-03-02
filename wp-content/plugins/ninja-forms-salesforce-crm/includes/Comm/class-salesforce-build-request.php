<?php

/**
 *
 *
 * @author Stuart Sequeira
 */
class SalesforceBuildRequest {

    /**
     * Field array from form submission
     *
     * Indexed array of associative array with these key-value pairs:
     * 'form_field' , 'field_map' , 'special_instructions'
     * @var array
     */
    protected $field_array;

    /**
     * Array specifying in which order to process Salesforce Objects
     *
     * Associative array with this format:
     *  [ {salesforce object} ] = 'num' where num is a string value of an integer
     *
     * @var array
     */
    protected $object_order_array;

    /**
     * Request array as iterated from form
     *
     * Associative array with this structure:
     * [ {salesforce object} ][ {salesforce_field} ] = {submission value}
     *
     * @var array
     */
    protected $unprioritized_request_array;

    /**
     * Request array sorted into processing order
     *
     * Associative array with this structure:
     * [ {salesforce object} ][ {salesforce_field} ] = {submission value}
     *
     * @var array
     */
    protected $request_array;

    /**
     * Array of objects for which to perform duplicate check
     *
     * Associative array of objects with nested arrays of salesforce field and
     * submission values for which to check
     *
     * [ {salesforce object} ] =>
     *      array(
     *          'salesforce_field' => {salesforce field} ,
     *          'user_value' => {submission value} )
     *      ),
     *      array(
     *          'salesforce_field' => {salesforce field} ,
     *          'user_value' => {submission value} )
     *      ),
     *
     * @var array
     */
    protected $duplicate_check_array;

    /**
     * Index array of Salesforce objects in processing order
     * @var array
     */
    protected $object_request_list;

    /**
     * Associative array of parent objects and their children
     *
     * [ {parent object} ] => array(
     *      {child object} => {child object field into which the parent ID is inserted}
     *      {child object} => {child object field into which the parent ID is inserted}
     * )
     *
     * @var array
     */
    protected $child_object_array;

    function __construct() {

        $this->build_object_order();

        $this->build_child_object_array();
    }

    public function setFieldArray( $field_array ) {

        $this->field_array = $field_array;
    }

    public function iterateFieldArray() {

        $this->iterate_nf3_array(); // Iterate 3.0 array


        if ( !$this->validate_unprioritized_request_array() ) {

            return false;
        }

        $this->reorder_request_array();

        $this->build_object_request_list();
    }

    /*
     * Receives a newly created object and its id.  Searches the child object
     * array to find child dependencies.  If any children are in the current
     * request array, add the new id in its linking field so that the child
     * object will be linked to the newly created object
     *
     */

    public function link_child_objects( $salesforce_object, $new_record_id ) {

        if ( isset( $this->child_object_array [ $salesforce_object ] ) && is_array( $this->child_object_array[ $salesforce_object ] ) ) {

            foreach ( $this->child_object_array[ $salesforce_object ] as $child_object => $field_link ) {

                if ( isset( $this->request_array[ $child_object ] ) ) {
                    $this->request_array[ $child_object ][ $field_link ] = $new_record_id;
                }
            }
        }
    }

// Internal Methods
    /**
     * Creates a default array of the order in which objects must be posted
     * to ensure that the following objects can be linked to the newly
     * created object's ID
     *
     */
    protected function build_object_order() {

        $default_object_order_array = array(
            'Account' => '10',
            'Contact' => '20',
            'Lead' => '25',
            'Opportunity' => '30',
            'Task' => '35',
            'Case' => '37',
            'Event' => '40',
            'Note' => '45',
            'Attachment' => '50',
            'CampaignMember' => '50',
        );

        $this->object_order_array = apply_filters( 'nfsalesforcecrm_filter_object_order', $default_object_order_array );
    }

    /**
     * Creates a default array of parent objects containing an array of
     * child objects and the field name that links the child to the
     * parent.
     *
     */
    protected function build_child_object_array() {

        $default_child_object_array = array(
            'Account' => array(
                'Contact' => 'AccountId',
                'Opportunity' => 'AccountId',
                'Task' => 'WhatId',
                'Case' => 'AccountId',
                'Event' => 'WhatId',
                'Note' => 'ParentId',
                'Attachment' => 'ParentId',
            ),
            'Contact' => array(
                'Task' => 'WhoId',
                'Case' => 'ContactId',
                'Event' => 'WhoId',
                'Note' => 'ParentId',
                'Attachment' => 'ParentId',
                'CampaignMember' => 'ContactId',
            ),
            'Lead' => array(
                'Task' => 'WhoId',
                'Event' => 'WhoId',
                'Note' => 'ParentId',
                'CampaignMember' => 'LeadId',
            )
        );

        $this->child_object_array = apply_filters( 'nfsalesforcecrm_filter_child_object_array', $default_child_object_array );
    }

    /**
     * Iterates array structure for NF3 and sends it to process method
     *
     */
    protected function iterate_nf3_array() {

        foreach ( $this->field_array as $field ) {

            $this->processField( $field );
        }
    }

    protected function processField( $field ) {

        // extract ojbect and field
        $map_args = $this->extractObjectAndFieldMap( $field[ 'field_map' ] );

        $object = $map_args[ 'object' ];

        $salesforce_field = $map_args[ 'salesforce_field' ];

        // validate form value
        $validated_form_value = $this->validateFormValue( $field );

        $this->unprioritized_request_array[ $object ][ $salesforce_field ] = $validated_form_value;

        // Check that duplicate field check is set to true; if not, continue on to next field
        if ( 'DuplicateCheck' !== $field[ 'special_instructions' ] ) {
            return;
        }

        /*
         * NOTE: duplicate check is built as an array of arrays so that
         * multiple matches could be added in the future if needed
         *
         */
        $this->duplicate_check_array[ $object ][] = array(
            'salesforce_field' => $salesforce_field,
            'user_value' => $validated_form_value
        );
    }

    protected function validateFormValue( $field ) {

        $in_process = $field[ 'form_field' ];

        // convert array to delimited string
        if ( is_array( $in_process ) && $field[ 'special_instructions' ] != 'FileUpload' ) {

            $delimiter = apply_filters( 'nfsalesforcecrm_array_delimiter', ',' );

            $in_process = implode( $delimiter, $in_process );
        }

        switch ( $field[ 'special_instructions' ] ) {

            case 'FileUpload':
                $validated_form_value = $this->validateFileUpload( $in_process );
                break;

            case 'DateInterval':
                $validated_form_value = $this->validateDateInterval( $in_process );
                break;

            /* 
             * 20180917 - if the date field is empty, set value to null so that 
             * no value is sent.  Confirmed that if someone deliberately sets
             * 01/01/1970 the value does get sent
             */
            case 'DateFormat':
                if(empty($in_process)){
                    $validated_form_value = null;
                }else{
                    $validated_form_value = $this->validateDateFormat( $in_process );
                }
                
                break;

            case 'SalesforceCurrency':
                $validated_form_value = $this->formatSalesforceCurrency( $in_process );
                break;

            case 'KeepCharacters':
                $validated_form_value = $this->keepCharacters( $in_process );
                break;

            case 'ForceBoolean':
                $validated_form_value = $this->forceBoolean( $in_process );
                break;

            case 'SemicolonDelimiter':
                $delimiter = ';';
                $validated_form_value = str_replace(',', $delimiter, $in_process);
                break;
            
            default:
                $validated_form_value = $in_process;
        }

        return $validated_form_value;
    }

    protected function validateDateFormat( $incoming_value ) {

        $date_format = apply_filters( 'nfsalesforcecrm_filter_date_interval_format', 'Y-m-d' );

        $original_date = strtotime( $incoming_value );

        $outgoing_value = date( $date_format, $original_date );

        return $outgoing_value;
    }

    protected function forceBoolean( $incoming_value ) {

        $temp_value = $incoming_value;

        $false_values = apply_filters( 'nfsalesforcecrm_boolean_false_values', array(
            '0',
            'false',
            'False',
            'FALSE',
            'unchecked',
            'Unchecked',
                ) );

        if ( in_array( $temp_value, $false_values ) ) {

            $temp_value = FALSE;
        } else {

            $temp_value = TRUE;
        }

        $outgoing_value = boolval( $temp_value );

        return $outgoing_value;
    }

    /**
     * Remove any html tags but keep the special characters like apostrophe
     * and ampersand
     *
     * TODO: determine how to handle this unused method
     */
    protected function keepCharacters( $incoming_value ) {

        $temp_value = $incoming_value;

        $decoded = html_entity_decode( $raw_form_value );
        $stripped = wp_strip_all_tags( $decoded );
        $test_stripped = $stripped;

        $outgoing_value = $temp_value;

        return $outgoing_value;
    }

    protected function validateDateInterval( $incoming_value ) {

        $date = new DateTime(); // get a timestamp

        $date_format = apply_filters( 'nfsalesforcecrm_filter_date_interval_format', 'Y-m-d' );

        date_add( $date, date_interval_create_from_date_string( $incoming_value ) );

        $outgoing_value = $date->format( $date_format );

        return $outgoing_value;
    }

    protected function validateFileUpload( $incoming_value ) {

        $contents = nfsalesforcecrm_extract_upload_contents( $incoming_value );

        if ( $contents ) {

            $outgoing_value = base64_encode( $contents );
        } else {

            $outgoing_value = $incoming_value;
        }

        return $outgoing_value;
    }

    /**
     * Converts incoming field into Salesforce currency format (integer)
     *
     * Added February 2018
     * @param mixed $value_in
     * @return integer
     */
    protected function formatSalesforceCurrency( $value_in ) {

        if ( is_array( $value_in ) ) {

            $value_in = 0;
        }

        $stripped = preg_replace( '/[^0-9.]*/', "", $value_in );

        $value_out = intval( $stripped );

        return $value_out;
        ;
    }

    /**
     * Check if unprioritized request array has any values before proceeding
     * If not, update comm data and return false to halt processing of form
     *
     */
    protected function validate_unprioritized_request_array() {

        $build_request_array = array();

        if ( !isset( $this->unprioritized_request_array ) || empty( $this->unprioritized_request_array ) ) {

            $build_request_array[ 'debug' ][ 'build_request' ][] = array(
                'heading' => 'Form Design Issue:',
                'value' => 'No fields were selected to map to Salesforce in the most recent request'
            );
            $build_request_array[ 'status' ][] = __( 'No fields were submitted in the last request', 'ninja-forms-salesforce-crm' );

            nfsalesforcecrm_update_comm_data( $build_request_array );

            return false;
        }
        return true;
    }

    /**
     * Sorts the unprioritized array into order needed for processing
     * Done by adding the two-digit object order to the front of each
     * object, sorting on the object key, then removing the object order
     *
     */
    protected function reorder_request_array() {

        foreach ( $this->unprioritized_request_array as $object => $array ) {

            if ( array_key_exists( $object, $this->object_order_array ) ) {

                $temp_object = $this->object_order_array[ $object ] . $object;
            } else {
                $temp_object = '99' . $object;
            }

            $temp_array[ $temp_object ] = $array;
        }

        ksort( $temp_array );

        foreach ( $temp_array as $object => $array ) {

            $stripped_object = substr( $object, 2 );

            $this->request_array[ $stripped_object ] = $array;
        }
    }

    /**
     * Builds a list of the objects from the prioritized list for iteration
     * This is done so that after each new record added, the request array
     * can be modified to insert the newly created object id for linking
     *
     */
    protected function build_object_request_list() {

        $this->object_request_list = array_keys( $this->request_array );
    }

    /**
     * Receives a string of the map argument set by the field registration
     *
     * Explodes the map argument into a salesforce object and a salesforce field
     *
     * @param string $map_args
     * @return array
     *
     */
    protected function extractObjectAndFieldMap( $map_args ) {

        $exploded_map_args = explode( '.', $map_args );

        $object = $exploded_map_args[ 0 ];

        $salesforce_field = $exploded_map_args[ 1 ];


        $return_array = array(
            'object' => $object,
            'salesforce_field' => $salesforce_field
        );

        return $return_array;
    }

// Gets and Sets

    public function get_request_array() {

        if ( !isset( $this->request_array ) || !is_array( $this->request_array ) ) {

            return false;
        } else {

            return $this->request_array;
        }
    }

    public function get_duplicate_check_array() {

        if ( !isset( $this->duplicate_check_array ) || !is_array( $this->duplicate_check_array ) ) {

            return false;
        } else {

            return $this->duplicate_check_array;
        }
    }

    public function get_object_request_list() {


        if ( !isset( $this->object_request_list ) || !is_array( $this->object_request_list ) ) {

            return false;
        } else {

            return $this->object_request_list;
        }
    }

    public function get_object_field_list( $object ) {

        if ( !isset( $this->request_array [ $object ] ) || !is_array( $this->request_array[ $object ] ) ) {

            return false;
        } else {

            return $this->request_array[ $object ];
        }
    }

}
