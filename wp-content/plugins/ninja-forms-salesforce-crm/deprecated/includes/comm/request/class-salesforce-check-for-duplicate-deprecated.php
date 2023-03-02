<?php

/**
 * checks for a duplicate record by a given field in a given object
 * 
 * Receives parameter_array
 *      'instance' =>
 *      'version_url' =>
 *      'access_token' =>
 *      'object_name' =>
 *      'field_name' =>
 *      'field_value' =>
 * 
 * @author Stuart Sequeira
 * 
 */
class SalesforceDuplicateCheck extends SalesforceCommunicationV2_9 {

    protected $object_name , $field_name , $field_value;
    protected $query_string;
    protected $duplicate_check_response;

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'instance' ] ) || !isset( $this->parameter_array[ 'version_url' ] ) || !isset( $this->parameter_array[ 'access_token' ] ) ) {

            $this->parameter_gatekeeper = false;
            return;
        }

        $this->instance = $this->parameter_array[ 'instance' ];
        $this->version_url = $this->parameter_array[ 'version_url' ];
        $this->access_token = $this->parameter_array[ 'access_token' ];
        $this->object_name = $this->parameter_array[ 'object_name' ];
        $this->field_name = $this->parameter_array[ 'field_name' ];
        $this->field_value = $this->parameter_array[ 'field_value' ];
        return;
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => __( 'I was successful checking for duplicates', 'ninja-forms-salesforce-crm' ),
            'wp_error_status' => __( 'WordPress had an internal error trying to check for duplicates', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-check-for-duplicate.process_wp_error',
            'unsuccessful_400_status' => __( 'My request to check for duplicates was rejected by Salesforce for the following reason:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-check-for-duplicate.process_bad_request_400',
            'unhandled_response_code_status' => __( 'Unhandled error code provided by Salesforce.  See raw data for details', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-check-for-duplicate.process_unhandled_response_codes',
            'parameter_gatekeeper_status' => __( 'Missing parameters for performing duplicate check', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-check-for-duplicate.process_failed_parameter_gatekeeper',
        );
    }

    protected function build_url() {
        
        $this->build_query_string();
           
        $this->url = $this->instance . $this->version_url . '/query/?q=' . $this->query_string;

        return;
    }

    protected function build_query_string(){
        
        $this->query_string = ''; // initialize
        $this->query_string .= 'SELECT ' . $this->field_name;
        $this->query_string .= ' FROM ' . $this->object_name;
        $this->query_string .= ' WHERE ' . $this->field_name." = '" . $this->field_value."'";
        
        $this->query_string = urlencode($this->query_string);
    }
    protected function build_final_http_args() {

        $headers_array = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token )
        );

        $this->final_http_args = array_merge( $this->default_http_args, $headers_array );

        $this->final_http_args[ 'method' ] = 'GET';

        return;
    }

    protected function extract_data_from_response_body() {

        $this->result = 'success';
        $this->status = $this->response_messages[ 'successful_20x_status' ];

        $temp_array = json_decode( $this->raw_response[ 'body' ], true );

        $this->body_array = $temp_array;

        $this->duplicate_check_response = $this->body_array;
    }

// Sets and Gets
    public function get_duplicate_check_response() {

        if ( isset( $this->duplicate_check_response ) ) {

            return $this->duplicate_check_response;
        } else {

            return false;
        }
    }

}
