<?php

/**
 * Structure of returned array
 *  'result' 
 *  'comm_data'
 *      //-comm_data_array structure-//
 *      )
 *  'data'=>array(
 *      'refresh_token'=>,
 *      'consumer_key,
 *      'consumer_secret'=>,
 *      

 *   )
 * 
 */
class SalesforceSecurityCredentials_V2_9 {

    private $nfsalesforcecrm_settings;
    private $credentials_array;

    function __construct( $nfsalesforcecrm_settings ) {

        $this->nfsalesforcecrm_settings = $nfsalesforcecrm_settings;

        $this->credentials_array = array(
            'result' => 'success',
            'comm_data' => array(),
            'data' => array()
        );

        $this->extract_consumer_key();
        $this->extract_consumer_secret();
        $this->extract_refresh_token();

        $this->get_credentials_array(); // automatically calls the method to return the array
    }

    private function extract_consumer_key() {

        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'consumer_key' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Some of the needed Salesforce login credentials is missing.  Please see raw data for details.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Consumer Key',
                'value' => __( 'Please enter your consumer key', 'ninja-forms-salesforce-crm' )
            );
        }
    }

    private function extract_consumer_secret() {
        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'consumer_secret' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Some of the needed Salesforce login credentials is missing.  Please see raw data for details.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Consumer Secret',
                'value' => __( 'Please enter your consumer secret', 'ninja-forms-salesforce-crm' )
            );
        }
    }

    private function extract_refresh_token() {
        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'refresh_token' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Some of the needed Salesforce login credentials is missing.  Please see raw data for details.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Refresh Token',
                'value' => __( 'Please generate an authorization code and a refresh token', 'ninja-forms-salesforce-crm' )
            );
        }
    }

    private function extract_password() {
        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_password' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_password' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'password' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_password' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Some of the needed Salesforce login credentials is missing.  Please see raw data for details.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Password',
                'value' => __( 'Please enter your password', 'ninja-forms-salesforce-crm' )
            );
        }
    }

// Sets and Gets

    public function get_credentials_array() {

        if ( empty( $this->credentials_array ) ) {

            return false;
        } else {
            return $this->credentials_array;
        }
    }

    public function get_comm_data() {

        if ( empty( $this->comm_data ) ) {

            return false;
        } else {
            return $this->comm_data;
        }
    }

}
