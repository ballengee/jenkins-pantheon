<?php

/**
 * Structure of array
  'result' =>
  'comm_data'
  'status'
  'debug'[]
  ['heading'=> , 'value'=>]
  ['heading'=> , 'value'=>]
  'data'

  @return boolean if empty, array if not empty
 *
 * @author Stuart Sequeira
 * 
 */
abstract class SalesforceCommunicationV2_9 {

    protected $url;
    protected $default_http_args;
    protected $final_http_args;
    protected $parameter_array;
    protected $parameter_gatekeeper; // boolean to halt execution without needed parameters
    protected $raw_response;
    protected $body_array;
    protected $processed_result_array;
    protected $result;
    protected $status;
    protected $response_messages;
    protected $error_array;

    function __construct( $parameter_array = array() ) {

        $this->parameter_array = $parameter_array;
        $this->parameter_gatekeeper = true;
        $this->make_it_so();
    }

// Internal Methods

    protected function make_it_so() {

        $this->extract_needed_parameters(); // ensure the parameters needed have been passed

        $this->build_response_messages(); // customize the response message for optimized support

        if ( !$this->parameter_gatekeeper ) {
            $this->process_failed_parameter_gatekeeper();
            return;
        }

        $this->build_default_http_args(); // set the default communication settings

        $this->build_final_http_args(); // Set headers and body arguments specific to the class and request being made

        $this->build_url(); // each request builds its own url to specifications

        $this->retrieve_request(); // send the request to Salesforce

        $this->process_request(); // handles Salesforce's response
    }

    abstract protected function extract_needed_parameters();

    private function build_default_http_args() {

        $this->default_http_args = array(
            'timeout' => 45,
            'redirection' => 0,
            'httpversion' => '1.0',
            'sslverify' => false,
            'method' => 'POST'
        );
    }

    abstract protected function build_final_http_args();

    abstract protected function build_url();

    abstract protected function build_response_messages();

    private function retrieve_request() {

        $this->raw_response = wp_remote_post( $this->url, $this->final_http_args );
    }

    private function process_request() {

        if ( is_wp_error( $this->raw_response ) ) {

            $this->process_wp_error();
            return;
        }

        if ( isset( $this->raw_response[ 'response' ][ 'code' ] ) ) {

            switch ($this->raw_response[ 'response' ][ 'code' ]) {

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

    abstract protected function extract_data_from_response_body();

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
                        'value' => serialize($this->final_http_args)
                    ),
                )
            )
        );
    }

    /**
     * Analyzes the body of a 400 response to extract errors
     * Some errors are a single key-value pair while others are
     * an indexed array of key value pairs.  There are also different
     * keys used for the errors.  These must all be handled without
     * creating an error.
     * 
     * @return array $this->error_array
     * 
     */
    private function extract_body_error_400_messages() { // creates an array of each error that occurs in the instance
        $json_decoded_error_body = json_decode( $this->raw_response[ 'body' ], true );

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

    /*
     * Branched from unhandled error processing; uses default value in case
     * all extended classes don't have messages for 403
     */
    private function process_forbidden_403() {

        $this->result = 'failure';
        $this->status = __('Salesforce has not enabled communication for your account.  Please check with your Salesforce representative', 'ninja-forms-salesforce-crm'); // initialize
        $last_comm_update = $this->response_messages[ 'unhandled_response_code_last_update' ]; // initialize

        if(isset($this->response_messages[ 'unsuccessful_403_status' ])){
            
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

    public function get_processed_result_array() {

        if ( empty( $this->processed_result_array ) ) {
            return false;
        } else {
            return $this->processed_result_array;
        }
    }

    public function get_result() {

        if ( empty( $this->processed_result_array[ 'result' ] ) ) {
            return false;
        } else {
            return $this->processed_result_array[ 'result' ];
        }
    }

    public function get_comm_data() {

        if ( empty( $this->processed_result_array[ 'comm_data' ] ) ) {
            return false;
        } else {
            return $this->processed_result_array[ 'comm_data' ];
        }
    }

}
