<?php

/**
 * Abstract class standardizing communication with Salesforce
 * 
 */
abstract class SalesforceCommunication {

    /**
     * URL at which request is made
     *
     * @var string
     */
    protected $url;

    /**
     * Default HTTP args structured as used by wp_remote_post
     *      
     * @var array
     */
    protected $default_http_args;

    /**
     * Finalized HTTP args after run-time adjustments   
     *
     * @var array
     */
    protected $final_http_args;

    /**
     * Incoming array of parameters
     *
     * @var array
     */
    protected $parameter_array;

    /**
     * Boolean to halt execution without needed parameters
     *
     * If parameter gatekeeper fails, communication is halted.  Each child makes
     * the determination if the parameters are valid
     * @var bool
     */
    protected $parameter_gatekeeper;  

    /**
     * Raw response from Salesforce structure by wp_remote_post
     *
     * @var array
     */
    protected $raw_response;

    /**
     * 'body' key from $raw_response
     *
     * @var array
     */
    protected $body_array;

    /**
     *  Internally process result of communication with Salesforce
     * 
     * 'result' => 'success' or 'failure'
     *  'comm_data' =>array(
     *      'status'
     *      'debug' => array(
     *          array(  'heading'=> '', 'value'=> '')
     *          . . .
     *   'data' [OPTIONAL]
     * 
     * @var array
     */
    protected $processed_result_array;

    /**
     * Result of request - usually 'success' or 'failure'
     *
     * @var string
     */
    protected $result;

    /**
     * Description of result, usually defined by response messages
     *
     * A more detailed explanation of the 'result' property.  Gives needed
     * context to a failure by describing the error in more detail.
     * 
     * @var string
     */
    protected $status;

    /**
     * Keyed array of responses for each child class
     * 
     * These are the scenarios that must be defined:
     * 
     *  'successful_20x_status' => {string},
     *  'missing_data' => {string},
     *  'wp_error_status' => {string},
     *  'wp_error_last_update' => {string},
     *  'unsuccessful_400_status' => {string},
     *  'unsuccessful_400_last_update' => {string},
     *  'unhandled_response_code_status' => {string},
     *  'unhandled_response_code_last_update' => {string},
     *  'parameter_gatekeeper_status' => {string},
     *  'parameter_gatekeeper_last_update' => {string}
     *
     * @var array
     */
    protected $response_messages;
    protected $error_array;

    protected $supportingData =[];
    protected $logEntry=[];
    /**
     * Constructed with parameter array that is validated by child class
     * @param array $parameter_array
     */
    function __construct( $parameter_array = array() ) {

        $this->parameter_array = $parameter_array;
        $this->parameter_gatekeeper = true;
        $this->make_it_so();
    }

    /**
     * Process the communication request
     * 
     * @return void
     */
    protected function make_it_so() {
        $this->supportingData = [ ];

        $this->logEntry = [
            'timestamp' => time(),
            'logPoint' => \get_class($this) . '_make_it_so'
        ];

        $this->extract_needed_parameters(); // ensure the parameters needed have been passed

        $this->build_response_messages(); // customize the response message for optimized support

        if ( !$this->parameter_gatekeeper ) {


            $this->process_failed_parameter_gatekeeper();

            $this->logEntry['supportingData'] = json_encode($this->supportingData);

            NF_SalesforceCRM()->logger()->debug('failed parameter gatekeeper',$this->logEntry);

            return;
        }

        $this->build_default_http_args(); // set the default communication settings

        $this->build_final_http_args(); // Set headers and body arguments specific to the class and request being made
        $this->supportingData['finalHttpArgs']=$this->final_http_args;

        $this->build_url(); // each request builds its own url to specifications
        $this->supportingData['url']=$this->url;

        $this->retrieve_request(); // send the request to Salesforce

        $this->process_request(); // handles Salesforce's response
        $this->supportingData['bodyArray']=$this->body_array;

        $this->logEntry['supportingData'] = json_encode($this->supportingData);
        NF_SalesforceCRM()->logger()->debug('completed',$this->logEntry);
    }

    /**
     * Extract required values from the parameters provided on construct
     *
     * @return void
     */
    abstract protected function extract_needed_parameters();

    /**
     * Build the default HTTP args as defined by structure in wp_remote_post
     * 
     * @return void
     * @since 3.1.0
     */
    private function build_default_http_args() {

        $sslverify = $this->setSSL();

        $this->default_http_args = array(
            'timeout' => 45,
            'redirection' => 0,
            'httpversion' => '1.0',
            'sslverify' => $sslverify,
            'method' => 'POST'
        );
    }

    /**
     * Sets default ssl_verify, applies filters then advanced commands
     * 
     * Default sslverify is true; modifies for nfsalesforcecrm_sslverify filter,
     * then checks if advanced command 'sslverify_false' is set to change it to 
     * false
     * 
     * @return boolean
     * @since 3.1.0
     */
    private function setSSL() {

        $sslverify = apply_filters( 'nfsalesforcecrm_sslverify', true );

        $advanced_code_setting = 'sslverify_false';

        $advanced_code = nfsalesforcecrm_advanced_code_is_set( $advanced_code_setting );

        if ( $advanced_code ) {

            $sslverify = false;
        }

        return $sslverify;
    }

    /**
     * Construct HTTP args defined by structure in wp_remote_post
     *
     * @return void
     */
    abstract protected function build_final_http_args();

    /**
     * Construct the URL used to make the request
     *
     * @return void
     */
    abstract protected function build_url();

    /**
     * Construct array of reponse messages based on predefined scenarios
     * 
     * @see $response_messages
     * @return void
     */
    abstract protected function build_response_messages();

    /**
     * Make the request, set response
     *
     * @return void
     */
    private function retrieve_request() {

        $this->raw_response = wp_remote_post( $this->url, $this->final_http_args );
    }

    /**
     * Process the raw response data into a known structure
     *
     * @return void
     */
    private function process_request() {

        if ( is_wp_error( $this->raw_response ) ) {

            $this->process_wp_error();
            return;
        }

        if ( isset( $this->raw_response[ 'response' ][ 'code' ] ) ) {
            
            $this->supportingData['responseCode']=$this->raw_response[ 'response' ][ 'code' ];

            switch ( $this->raw_response[ 'response' ][ 'code' ] ) {

                case 200:
                case 201:
                    $this->process_successful_response_20x();
                    break;

                case 400:
                    $this->process_bad_request_400();
                    break;

                case 403:
                    $this->process_forbidden_403();
                    break;
                default:
                    $this->process_unhandled_response_codes();
            }

            return;
        }
    }

    /**
     * Structure result when response code is 20x
     *
     * @return void
     */
    protected function process_successful_response_20x() {

        $this->extract_data_from_response_body();

        $this->processed_result_array = array(
            'result' => $this->result,
            'comm_data' => array(
                'status' => $this->status,
                'debug' => array(
                    array(
                        'heading' => 'Raw Response from Salesforce',
                        'value' => serialize( $this->raw_response )
                    ),
                    array(
                        'heading' => 'Body Array',
                        'value' => serialize( $this->body_array )
                    )
                )
            ),
            'data' => $this->body_array
        );
    }

    /**
     * Extract required data from the raw response
     *
     * @return void
     */
    abstract protected function extract_data_from_response_body();

    /**
     * Structure result when response code is 400
     *
     * @return void
     */
    private function process_bad_request_400() {

        $this->result = 'failure';
        $this->extract_body_error_400_messages();

        $this->status = $this->response_messages[ 'unsuccessful_400_status' ] . '<br />';

        foreach ( $this->error_array as $error ) {

            $this->status .= $error . '<br />';
        }

        $this->processed_result_array = array(
            'result' => $this->result,
            'comm_data' => array(
                'status' => $this->status,
                'debug' => array(
                    array(
                        'heading' => 'Raw Response from Salesforce',
                        'value' => serialize( $this->raw_response )
                    ),
                    array(
                        'heading' => 'Last comm update made:',
                        'value' => $this->response_messages[ 'unsuccessful_400_last_update' ]
                    ),
                    array(
                        'heading' => 'Failed Request:',
                        'value' => serialize( $this->final_http_args )
                    ),
                )
            )
        );
    }

    /**
     * Analyze the body of a 400 response to extract errors
     * 
     * Some errors are a single key-value pair while others are
     * an indexed array of key value pairs.  There are also different
     * keys used for the errors.  These must all be handled without
     * creating an error.
     * 
     * @return void
     * 
     */
    private function extract_body_error_400_messages() { // creates an array of each error that occurs in the instance
        $json_decoded_error_body = json_decode( $this->raw_response[ 'body' ], true );

        $this->supportingData['errorArray']=$json_decoded_error_body;
        
        if ( !is_array( $json_decoded_error_body ) ) { // handle some unknown condition of a non-array body response
            $this->error_array[] = $json_decoded_error_body;
            return;
        }

        if ( isset( $json_decoded_error_body[ 0 ] ) && is_array( $json_decoded_error_body[ 0 ] ) ) { // it is an indexed array of key value pairs
            foreach ( $json_decoded_error_body as $single_error ) {

                $this->error_array[] = serialize( $single_error );
            }
        } else { // it is a single associatiave array
            $this->error_array[] = implode( ' - ', $json_decoded_error_body );
        }
        return;
    }

    /**
     * Structure result when response code is 403
     *
     * @return void
     */
    private function process_forbidden_403() {

        $this->result = 'failure';
        $this->status = __( 'Salesforce has not enabled communication for your account.  Please check with your Salesforce representative', 'ninja-forms-salesforce-crm' ); // initialize
        $last_comm_update = $this->response_messages[ 'unhandled_response_code_last_update' ]; // initialize

        if ( isset( $this->response_messages[ 'unsuccessful_403_status' ] ) ) {

            $this->status = $this->response_messages[ 'unsuccessful_403_status' ];
            $last_comm_update = $this->response_messages[ 'unsuccessful_403_last_update' ];
        }

        $error_details = __( 'Code: ', 'ninja-forms-salesforce-crm' ) . $this->raw_response[ 'response' ][ 'code' ];
        $error_details .= ' - ' . $this->raw_response[ 'response' ][ 'message' ];

        $this->processed_result_array = array(
            'result' => $this->result,
            'comm_data' => array(
                'status' => $this->status,
                'debug' => array(
                    array(
                        'heading' => __( 'Error Code Details:', 'ninja-forms-salesforce-crm' ),
                        'value' => $error_details
                    ),
                    array(
                        'heading' => 'Raw Response from Salesforce',
                        'value' => serialize( $this->raw_response )
                    ),
                    array(
                        'heading' => 'Last comm update made:',
                        'value' => $last_comm_update
                    )
                )
            )
        );
    }

    /**
     * Structure result when response code is not explicitly defined
     *
     * @return void
     */
    private function process_unhandled_response_codes() {

        $this->result = 'failure';
        $this->status = $this->response_messages[ 'unhandled_response_code_status' ];
        $last_comm_update = $this->response_messages[ 'unhandled_response_code_last_update' ];

        $error_details = __( 'Code: ', 'ninja-forms-salesforce-crm' ) . $this->raw_response[ 'response' ][ 'code' ];
        $error_details .= ' - ' . $this->raw_response[ 'response' ][ 'message' ];

        $this->processed_result_array = array(
            'result' => $this->result,
            'comm_data' => array(
                'status' => $this->status,
                'debug' => array(
                    array(
                        'heading' => __( 'Error Code Details:', 'ninja-forms-salesforce-crm' ),
                        'value' => $error_details
                    ),
                    array(
                        'heading' => 'Raw Response from Salesforce',
                        'value' => serialize( $this->raw_response )
                    ),
                    array(
                        'heading' => 'Last comm update made:',
                        'value' => $last_comm_update
                    )
                )
            )
        );
    }

    /**
     * Structure result when response is a WP_Error
     *
     * @return void
     */
    private function process_wp_error() {

        $this->result = 'failure';
        $this->status = $this->response_messages[ 'wp_error_status' ];
        $last_comm_update = $this->response_messages[ 'wp_error_last_update' ];

        $this->processed_result_array = array(
            'result' => $this->result,
            'comm_data' => array(
                'status' => $this->status,
                'debug' => array(
                    array(
                        'heading' => 'WordPress Error:',
                        'value' => serialize( $this->raw_response )
                    ),
                    array(
                        'heading' => 'Last comm update made:',
                        'value' => $last_comm_update,
                    )
                )
            )
        );
    }

    /**
     * Structure response when parameter gatekeeper fails
     *
     * @return void
     */
    private function process_failed_parameter_gatekeeper() {

        $this->result = 'failure';
        $this->status = $this->response_messages[ 'parameter_gatekeeper_status' ];
        $last_comm_update = $this->response_messages[ 'parameter_gatekeeper_last_update' ];

        $this->processed_result_array = array(
            'result' => $this->result,
            'comm_data' => array(
                'status' => $this->status,
                'debug' => array(
                    array(
                        'heading' => 'Last comm update made:',
                        'value' => $last_comm_update,
                    )
                )
            )
        );
    }

// Sets and Gets

    /**
     * Return processed result array
     *
     * @return array|bool
     */
    public function get_processed_result_array() {

        if ( empty( $this->processed_result_array ) ) {

            return false;
        } else {

            return $this->processed_result_array;
        }
    }

    /**
     * Return the processed result 'result' value
     * 
     * 'failure' or child-defined value (should be 'success')
     *
     * @return void
     */
    public function get_result() {

        if ( empty( $this->processed_result_array[ 'result' ] ) ) {

            return false;
        } else {

            return $this->processed_result_array[ 'result' ];
        }
    }

    /**
     * Return comm data array
     * 
     * @todo Standardize comm data as entity object
     *
     * @return void
     */
    public function get_comm_data() {

        if ( empty( $this->processed_result_array[ 'comm_data' ] ) ) {

            return false;
        } else {

            return $this->processed_result_array[ 'comm_data' ];
        }
    }

    /**
     * Returns the Raw Response; false if empty
     * 
     * @return array|mixed
     * @since 3.1.0
     */
    public function getRawResponse(){
         if ( empty( $this->raw_response ) ) {

            return false;
        } else {

            return $this->raw_response;
        }       
        
    }
    
}
