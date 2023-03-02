<?php

/**
 * Uses the user settings to build an array of parameters needed to access
 * the Salesforce API
 * 
 * Creates an instance of SalesforceSecurityCredentials to build security credentials
 * Creates an instance of SalesforceAccessToken from the security credentials
 * Creates an instance of SalesforceVersion from the access token
 * 
 * @global array $nfsalesforcecrm_settings
 * @return array
 *      api_parameter_array
 *        'result' =>
 *        'status' =>
 *        'access_token' =>
 *        'instance' =>
 *        'version_url' =>
 * 
 */
function nfsalesforcecrm_retrieve_api_parameters() {

    global $nfsalesforcecrm_settings;

    $api_parameter_array =  array(
        'result'=>'success',
        'status'=>'',
        'access_token'=>'',
        'instance'=>'',
        'version_url'=>'',
    );

    $credentials = new SalesforceSecurityCredentials( $nfsalesforcecrm_settings );
    $credentials_array = $credentials->get_credentials_array();

    if ( 'success' != $credentials_array[ 'result' ] ) {
        
        $api_parameter_array['result']=$credentials_array['result'];
        $api_parameter_array['status']=$credentials_array['comm_data']['status'];
        
        return $api_parameter_array;
    }

    /*
     * Use the credentials array to generate an access token and instance that
     * will be needed for all future requests made to Salesforce during the
     * session.
     */
    $access_token_array = array(
        'credentials_array' => $credentials_array[ 'data' ]
    );

    $access_token_object = new SalesforceAccessToken( $access_token_array );
    $token_result = $access_token_object->get_result();
    $temp_token_comm_data = $access_token_object->get_comm_data();

    if ( 'success' != $token_result ) {

        $api_parameter_array['result'] = $token_result;
        $api_parameter_array['status']=$temp_token_comm_data[ 'status' ];

        return $api_parameter_array;
    }

    $api_parameter_array[ 'access_token' ] = $access_token_object->get_access_token();
    $api_parameter_array[ 'instance' ] = $access_token_object->get_instance_url();

    /*
     * Create the version object
     * 
     * Extract the latest version
     * 
     */
    $version_object = new SalesforceVersion( $api_parameter_array );
    
    $version_result = $version_object->get_result();
    
    if ( 'success' != $version_result ) {

        $api_parameter_array[ 'result' ] = $version_result;
        $api_parameter_array[ 'status' ] = $version_object[ 'status' ];

        return $api_parameter_array;
    }

    $api_parameter_array[ 'version_url' ] = $version_object->get_version_url();


    return $api_parameter_array;
}
