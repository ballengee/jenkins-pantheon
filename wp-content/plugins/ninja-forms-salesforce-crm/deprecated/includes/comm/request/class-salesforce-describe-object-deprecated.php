<?php

/**
 * Returns the description of a given object
 * 
 * Receives parameter_array
 *      'instance' =>
 *      'version_url' =>
 *      'access_token' =>
 *      'object_name' =>
 * 
 * @author Stuart Sequeira
 * 
 */
class SalesforceDescribeObject extends SalesforceCommunicationV2_9 {

    protected $object_description;

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'instance' ] ) || !isset( $this->parameter_array[ 'version_url' ] ) || !isset( $this->parameter_array[ 'access_token' ] ) ) {

            $this->parameter_gatekeeper = false;
            return;
        }

        $this->instance = $this->parameter_array[ 'instance' ];
        $this->version_url = $this->parameter_array[ 'version_url' ];
        $this->access_token = $this->parameter_array[ 'access_token' ];
        $this->object_name = $this->parameter_array[ 'object_name' ];
        return;
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => __( 'I was successful describing an object', 'ninja-forms-salesforce-crm' ),
            'wp_error_status' => __( 'WordPress had an internal error trying to retrive a field list from Salesforce', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-describe-object.process_wp_error',
            'unsuccessful_400_status' => __( 'My request for describing an object was rejected by Salesforce for the following reason:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-describe-object.process_bad_request_400',
            'unhandled_response_code_status' => __( 'Unhandled error code provided by Salesforce.  See raw data for details', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-describe-object.process_unhandled_response_codes',
            'parameter_gatekeeper_status' => __( 'Missing parameters for describing an object', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-describe-object.process_failed_parameter_gatekeeper',
        );
    }

    protected function build_url() {

        $this->url = $this->instance . $this->version_url . '/sobjects/' . $this->object_name . '/describe/';

        return;
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

        $this->object_description = $this->body_array;
    }

// Sets and Gets
    public function get_object_description() {

        if ( isset( $this->object_description ) ) {

            return $this->object_description;
        } else {

            return false;
        }
    }

}
