<?php

/**
 * Builds an indexed array of object names
 * 
 * Receives parameter_array
 *      'instance' =>
 *      'version_url' =>
 *      'access_token' =?
 *
 * @author Stuart
 */
class SalesforceListOfObjects extends SalesforceCommunicationV2_9 {

    protected $list_of_objects;

// Internal Methods

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'instance' ] ) || !isset( $this->parameter_array[ 'version_url' ] ) || !isset( $this->parameter_array[ 'access_token' ] ) ) {

            $this->parameter_gatekeeper = false;
            return;
        }

        return;
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => __( 'I was successful retrieving a list of objects', 'ninja-forms-salesforce-crm' ) . '<br />',
            'wp_error_status' => __( 'WordPress had an internal error trying retrive a list of objects ', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-list-of-objects.process_wp_error',
            'unsuccessful_400_status' => __( 'My request was rejected by Salesforce for the following reason:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-list-of-objects.process_bad_request_400',
            'unhandled_response_code_status' => __( 'Unhandled error code provided by Salesforce.  See raw data for details', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-list-of-objects.process_unhandled_response_codes',
            'parameter_gatekeeper_status' => __( 'Missing parameters for retrieving a list of objects', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-list-of-objects.process_failed_parameter_gatekeeper',
            'unsuccessful_403_status'=>__('Salesforce has not enabled communication for your account.  Please check with your Salesforce representative', 'ninja-forms-salesforce-crm'),
            'unsuccessful_403_last_update' => 'class-salesforce-list-of-objects.process_bad_request_403',
        );
    }

    protected function build_url() {

        $this->url = $this->parameter_array[ 'instance' ] . $this->parameter_array[ 'version_url' ] . '/sobjects/';

        //print_r($this->url);
        return;
    }

    protected function build_final_http_args() {

        $headers_array = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->parameter_array[ 'access_token' ] )
        );

        $this->final_http_args = array_merge( $this->default_http_args, $headers_array );

        $this->final_http_args[ 'method' ] = 'GET';

        return;
    }

    protected function extract_data_from_response_body() {

        $this->result = 'success';
        $this->status = $this->response_messages[ 'successful_20x_status' ];

        $temp_array = json_decode( $this->raw_response[ 'body' ], true );

        foreach ( $temp_array[ 'sobjects' ] as $object_array ) {

            $this->body_array[] = $object_array[ 'name' ];
        }

        $this->list_of_objects = $this->body_array;
    }

// Sets and Gets
    public function get_list_of_objects() {

        if ( isset( $this->list_of_objects ) ) {

            return $this->list_of_objects;
        } else {

            return false;
        }
    }

}
