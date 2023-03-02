<?php

/**
 * Posts a new record into the specified object
 *
 * @author Stuart Sequeira
 */
class SalesforcePostNewRecordObject_v2_9 extends SalesforceCommunicationV2_9 {

    protected $new_record_id;

// Internal Methods

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'instance' ] ) || !isset( $this->parameter_array[ 'version_url' ] ) || !isset( $this->parameter_array[ 'access_token' ] ) || !isset( $this->parameter_array[ 'object_name' ] ) || !isset( $this->parameter_array[ 'field_array' ] ) ) {

            $this->parameter_gatekeeper = false;
            return;
        }

        return;
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => $this->parameter_array[ 'object_name' ] . __( ': I was successful adding this new record to your Salesforce account: ', 'ninja-forms-salesforce-crm' ),
            'wp_error_status' => $this->parameter_array[ 'object_name' ] . __( ': WordPress had an internal error trying add a new record to Salesforce', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-post-new-record.process_wp_error',
            'unsuccessful_400_status' => $this->parameter_array[ 'object_name' ] . __( ': My request to add this record was rejected for the reasons listed below:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-post-new-record.process_bad_request_400',
            'unhandled_response_code_status' => $this->parameter_array[ 'object_name' ] . __( ': Unhandled error code provided by Salesforce while trying to add this record type.  See raw data for details', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-post-new-record.process_unhandled_response_codes',
            'parameter_gatekeeper_status' => __( 'Missing parameters for adding a new record', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-post-new-record.process_failed_parameter_gatekeeper',
        );
    }

    protected function build_url() {

        $this->url = $this->parameter_array[ 'instance' ] . $this->parameter_array[ 'version_url' ] . '/sobjects/' . $this->parameter_array[ 'object_name' ];

        return;
    }

    protected function build_final_http_args() {

        $headers_array = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->parameter_array[ 'access_token' ],
                'Content-Type' => 'application/json' )
        );


        $body_array = array(
            'body' => json_encode( $this->parameter_array[ 'field_array' ] )
        );


        $this->final_http_args = array_merge( $this->default_http_args, $headers_array, $body_array );

        $this->final_http_args[ 'method' ] = 'POST';

        return;
    }

    protected function extract_data_from_response_body() {

        $temp_array = json_decode( $this->raw_response[ 'body' ], true );


        $this->result = 'success';
        $this->status = $this->response_messages[ 'successful_20x_status' ];


        $this->body_array = $temp_array;

        $this->new_record_id = $this->body_array[ 'id' ];
    }

// Sets and Gets

    public function get_new_record_id() {

        if ( empty( $this->new_record_id ) ) {
            return false;
        } else {
            return $this->new_record_id;
        }
    }

}
