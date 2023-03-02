<?php

/**
 * Grabs a list of available verions
 * Sets the version_url to the lates version
 * 
 * Receives parameter_array['instance']
 * @author Stuart Sequeira
 * 
 */
class SalesforceVersion extends SalesforceCommunication {

    protected $version_url;

// Internal Methods

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'instance' ] ) ) {

            $this->parameter_gatekeeper = false;
            return;
        }

        $this->instance = $this->parameter_array[ 'instance' ];
        return;
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => __( 'I was successful getting an version response from Salesforce', 'ninja-forms-salesforce-crm' ),
            'missing_data' => __( 'I communicated with Salesforce but could not retrieve a valid version.', 'ninja-forms-salesforce-crm' ),
            'wp_error_status' => __( 'WordPress had an internal error trying to request available versions from Salesforce', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-version.process_wp_error',
            'unsuccessful_400_status' => __( 'My request for available versions was rejected by Salesforce for the following reason:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-version.process_bad_request_400',
            'unhandled_response_code_status' => __( 'Unknown error whle trying to retrive Salesforce version', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-version.process_version_response',
            'parameter_gatekeeper_status' => __( 'Missing parameters for Version', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-version.process_failed_parameter_gatekeeper',
        );
    }

    protected function build_url() {

        $this->url = $this->instance . '/services/data/'; // URL for requesting version
        return;
    }

    protected function build_final_http_args() {

        $this->final_http_args = $this->default_http_args;
        $this->final_http_args[ 'method' ] = 'GET';

        return;
    }

    protected function extract_data_from_response_body() {

        $this->result = 'success';
        $this->status = $this->response_messages[ 'successful_20x_status' ];

        $this->body_array = json_decode( $this->raw_response[ 'body' ], true );

        $number_of_versions = count( $this->body_array );

        $this->version_url = $this->body_array[ $number_of_versions - 1 ][ 'url' ];

        $this->processed_result_array[ 'data' ][ 'url' ] = $this->version_url;
    }

// Sets and Gets

    public function get_version_url() {

        if ( isset( $this->version_url ) ) {

            return $this->version_url;
        } else {

            return false;
        }
    }

}
