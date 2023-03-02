<?php

/**
 * Given stored settings, extract credentials into known structure
 *
 * Provides communication data with credentials to provide technical assistance
 * to the class making the call for credentials
 */
class SalesforceSecurityCredentials {

    /**
     * Salesforce settings array
     *
     * Expected structure:
     *  [
     *      'nfsalesforcecrm_consumer_key'=>{string},
     *      'nfsalesforcecrm_consumer_secret'=>{string},
     *      'nfsalesforcecrm_refresh_token'=>{string}
     * ]
     * @var array
     */
    private $nfsalesforcecrm_settings;

    /**
     * Array of credentials, with support data
     * 
     * [
     *      'result' =>{string} ('success' or 'failure')
     *      'comm_data'=>[
     *          'status'=>{string},
     *          'debug' =>[
     *              [
     *                  'heading'=>{string},
     *                   'value'=>{string}
     *              ],
     *              ...
     *          ],
     *      'data'=>[
     *          'refresh_token'=>{string},
     *          'consumer_key=>{string},
     *          'consumer_secret'=>{string},   
     *      ]
     *  ]
     *
     * @var array
     */
    private $credentials_array;

    /**
     * Construct with array of raw credential data
     *
     *
     * @param [type] $nfsalesforcecrm_settings
     */
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

    /**
     * Extract consumer key and store in credentials property
     *
     * @return void
     */
    private function extract_consumer_key() {

        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'consumer_key' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Your Salesforce Consumer Key is not stored in Settings.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Consumer Key',
                'value' => __( 'Please enter your consumer key', 'ninja-forms-salesforce-crm' )
            );
        }
    }

    /**
     * Extract consumer secret and store in credentials property
     *
     * @return void
     */
    private function extract_consumer_secret() {
        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'consumer_secret' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Your Salesforce Consumer Secret is not stored in Settings.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Consumer Secret',
                'value' => __( 'Please enter your consumer secret', 'ninja-forms-salesforce-crm' )
            );
        }
    }

    /**
     * Extract refresh token and store in credentials property
     *
     * @return void
     */
    private function extract_refresh_token() {
        if ( isset( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ] ) && strlen( $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ] ) > 0 ) {

            $this->credentials_array[ 'data' ][ 'refresh_token' ] = $this->nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ];
        } else {

            $this->credentials_array[ 'result' ] = 'failure';
            $this->credentials_array[ 'comm_data' ][ 'status' ] = __( 'Your Refresh Token is missing or expired.  Please generate a new Authorization Code and Refresh Token.', 'ninja-forms-salesforce-crm' );
            $this->credentials_array[ 'comm_data' ][ 'debug' ][] = array(
                'heading' => 'Missing Refresh Token',
                'value' => __( 'Please generate an authorization code and a refresh token', 'ninja-forms-salesforce-crm' )
            );
        }
    }

    /**
     * Unused method - should be removed
     *
     * @return void
     * @todo Remove this unused method
     */
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

    /**
     * Return credentials array
     *
     * @return array|bool
     */
    public function get_credentials_array()
    {

        if (empty($this->credentials_array)) {

            return false;
        } else {
            return $this->credentials_array;
        }
    }

    /**
     * Unused method - should be removed
     *
     * @return void
     * @todo Remove this method after verifying disuse
     */
    public function get_comm_data() {

        if ( empty( $this->comm_data ) ) {

            return false;
        } else {
            return $this->comm_data;
        }
    }

}
