<?php

/**
 * Grabs the client_id, client_secret, and authorization_code from the settings
 * in the options database
 * 
 * Returns the refresh token and instance
 * 
 * Accessed on the Settings Pages, this class generates the token and instance 
 * to be stored in the options database for use during each form submission
 * 
 * Receives parameter_array
 *      'client_id' =>
 *      'client_secret' =>
 *      'authorization_code' =>
 * 
 * writes the refresh token directly into the options database
 * 
 * @author Stuart Sequeira
 * 
 */
class SalesforceRefreshToken extends SalesforceCommunication {

    protected $client_id, $client_secret, $authorization_code;
    protected $refresh_token;

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'client_id' ] ) || !isset( $this->parameter_array[ 'client_secret' ] ) || !isset( $this->parameter_array[ 'authorization_code' ] ) ) {

            $this->parameter_gatekeeper = false;
            return;
        }

        $this->client_id = $this->parameter_array[ 'client_id' ];
        $this->client_secret = $this->parameter_array[ 'client_secret' ];
        $this->authorization_code = $this->parameter_array[ 'authorization_code' ];

        return;
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => __( 'I was successful generating a refresh token', 'ninja-forms-salesforce-crm' ),
            'wp_error_status' => __( 'WordPress had an internal error trying to generate a refresh token', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-get-refresh-token.process_wp_error',
            'unsuccessful_400_status' => __( 'My request for getting a refresh token was rejected by Salesforce for the following reason:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-get-refresh-token.process_bad_request_400',
            'unhandled_response_code_status' => __( 'Unhandled error code provided by Salesforce.  See raw data for details', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-get-refresh-token.process_unhandled_response_codes',
            'parameter_gatekeeper_status' => __( 'Missing parameters for generating a refresh token', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-get-refresh-token.process_failed_parameter_gatekeeper',
        );
    }

    protected function build_url() {
        
        $nfsalesforcecrm_connection = apply_filters('nfsalesforcecrm_set_connection_type','login');
        $this->url = 'https://'.$nfsalesforcecrm_connection.'.salesforce.com/services/oauth2/token';
        ;

        return;
    }

    protected function build_final_http_args() {
        
        $nfsalesforcecrm_connection = apply_filters('nfsalesforcecrm_set_connection_type','login');
        
        $body = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => 'https://'.$nfsalesforcecrm_connection.'.salesforce.com/services/oauth2/success',
            'code' => $this->authorization_code
        );

        $this->final_http_args = $this->default_http_args;

        $this->final_http_args[ 'body' ] = $body;

        return;
    }

    protected function extract_data_from_response_body() {

        $this->result = 'success';
        $this->status = $this->response_messages[ 'successful_20x_status' ];

        $temp_array = json_decode( $this->raw_response[ 'body' ], true );

        $this->body_array = $temp_array;

        $this->instance_url = $this->body_array[ 'instance_url' ];
        $this->refresh_token = $this->body_array[ 'refresh_token' ];
    }

// Sets and Gets

    public function get_refresh_token() {

        if ( isset( $this->refresh_token ) ) {

            return $this->refresh_token;
        } else {

            return false;
        }
    }

}
