<?php

/**
 * receives an array
 *  'credentials_array'=>
 *      'refresh_token'=>,
 *      'consumer_key,
 *      'consumer_secret'=>,
 *
 *  returns access_token
 * @author Stuart Sequeira
 */
class SalesforceAccessTokenV2_9 extends SalesforceCommunicationV2_9 {

    protected $instance_url;
    protected $access_token;

// Internal Methods

    protected function extract_needed_parameters() {

        if ( !isset( $this->parameter_array[ 'credentials_array' ] ) ) {

            $this->parameter_gatekeeper = false;
        }
    }

    protected function build_response_messages() {

        $this->response_messages = array(
            'successful_20x_status' => __( 'I was successful getting an access token response from Salesforce', 'ninja-forms-salesforce-crm' ),
            'missing_data' => __( 'I communicated successfully with Salesforce but could not find the access token and instance', 'ninja-forms-salesforce-crm' ),
            'wp_error_status' => __( 'WordPress had an internal error trying to request an access token from Salesforce', 'ninja-forms-salesforce-crm' ),
            'wp_error_last_update' => 'class-salesforce-access-token.process_wp_error',
            'unsuccessful_400_status' => __( 'My request for an access token was rejected by Salesforce for the following reason:', 'ninja-forms-salesforce-crm' ),
            'unsuccessful_400_last_update' => 'class-salesforce-access-token.process_bad_request_400',
            'unhandled_response_code_status' => __( 'Unhandled error code provided by Salesforce.  See raw data for details', 'ninja-forms-salesforce-crm' ),
            'unhandled_response_code_last_update' => 'class-salesforce-access-token.process_unhandled_response_codes',
            'parameter_gatekeeper_status' => __( 'Missing parameters for Access Token', 'ninja-forms-salesforce-crm' ),
            'parameter_gatekeeper_last_update' => 'class-salesforce-access-token.process_failed_parameter_gatekeeper',
        );
    }

    protected function build_final_http_args() {

        $body_array = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->parameter_array[ 'credentials_array' ][ 'refresh_token' ], //	End-user’s username.
            'client_id' => $this->parameter_array[ 'credentials_array' ][ 'consumer_key' ], //	The Consumer Key from the connected app definition.
            'client_secret' => $this->parameter_array[ 'credentials_array' ][ 'consumer_secret' ], //The Consumer Secret from the connected app definition.
        );


        $this->final_http_args = array_merge( $this->default_http_args, array( 'body' => $body_array ) );

        return;
    }

    protected function build_url() {

        $nfsalesforcecrm_connection = apply_filters('nfsalesforcecrm_set_connection_type','login');
        $this->url = 'https://'.$nfsalesforcecrm_connection.'.salesforce.com/services/oauth2/token'; // URL for requesting access token
    }

    protected function extract_data_from_response_body() {

        $temp_array = json_decode( $this->raw_response[ 'body' ], true );
        $this->body_array = $temp_array;

        $this->result = 'success';
        $this->status = $this->response_messages[ 'successful_20x_status' ];


        if ( isset( $temp_array[ 'instance_url' ] ) ) {

            $this->instance_url = $temp_array[ 'instance_url' ];
        } else {

            $this->result = 'failure';
            $this->status = $this->response_messages[ 'missing_data' ];
        }

        if ( isset( $temp_array[ 'access_token' ] ) ) {

            $this->access_token = $temp_array[ 'access_token' ];
        } else {
            $this->result = 'failure';
            $this->status = $this->response_messages[ 'missing_data' ];
        }
    }

// Sets and Gets

    public function get_instance_url() {

        if ( isset( $this->instance_url ) ) {

            return $this->instance_url;
        } else {

            return false;
        }
    }

    public function get_access_token() {

        if ( isset( $this->access_token ) ) {

            return $this->access_token;
        } else {

            return false;
        }
    }

}
