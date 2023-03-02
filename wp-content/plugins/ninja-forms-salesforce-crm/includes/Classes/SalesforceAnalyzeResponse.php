<?php

/**
 * Given a full response, analyze and return pertinent information
 *
 * @author stuartlb3
 * @since 3.1.0
 */
class SalesforceAnalyzeResponse {

    /**
     * Incoming response for analysis
     * @var array
     */
    protected $raw_response;

    /**
     * Context of the response
     *
     * 'create' for creating a record, or 'getrecords' for getting records
     * @var string
     */
    protected $context;

    /**
     * HTTP response extracted from raw response
     *
     * If error or missing, set to false to stop process
     *
     * @var object WP_HTTP_Requests_Response class
     */
    protected $http_response;

    /**
     * Combined data and headers extracted from response
     *
     * Data structure contains at minimum this structure:
     * array(
     *   'id' => '', // comes from data on success
     *   'success' => false, // comes from data on success
     *   'errors' => array(), // manually extracted on fail
     *   'date' => '', // from header
     *   'location' => '' // from header
     * )
     *
     * @var array
     */
    protected $full_analysis;

    /**
     * Analyze current response
     */
    public function analyzeResponse() {

        $this->initVars();

        if ( $this->http_response ) {

            $extracted_data = $this->getData();
        }

        $header_object = $this->http_response->get_headers();

        $headers = $header_object->getAll();

        $this->full_analysis = array_merge( $this->full_analysis, $extracted_data, $headers );
    }

    /**
     *
     * On create success, raw data has this structure:
     * a:3:{
     *   s:2:"id";s:18:" { string } ";
     *   s:7:"success";b:1;
     *   s:6:"errors";a:0:{}
     * }
     *
     * On Duplicate Check
     * a:3{
     *  s:9:"totalSize";i:_
     *  s:4:"done";b:_
     *  s:7:"records";a:_{}
     * }
     * @return array Extracted data
     */
    protected function getData()
    {
        $raw_data = json_decode($this->http_response->get_data(), true);

        switch ($this->context) {
            case 'create':
                if (isset($raw_data[ 'success' ]) && $raw_data[ 'success' ]) {

                    $extracted_data = $raw_data;
                } elseif (is_array($raw_data)) {

                    $extracted_data[ 'errors' ] = $this->extractDataErrors($raw_data);
                }

                break;

            case 'getrecords':
                
                if (isset($raw_data[ 'totalSize' ])) {

                    $extracted_data = $raw_data;

                    $extracted_data[ 'success' ] = true;
                } elseif (is_array($raw_data)) {

                    $extracted_data[ 'errors' ] = $this->extractDataErrors($raw_data);
                }

                break;

            default:
                if (is_array($raw_data)) {

                    $extracted_data[ 'errors' ] = $this->extractDataErrors($raw_data);
                }
        }

        return $extracted_data;
    }

    /**
     *
     *
     *
     * On failure, raw data has this structure:
     * a:1:{
     *   i:0;a:3:{
     *     s:7:"message";s:39:" { human-readable string } ";
     *     s:9:"errorCode";s:22:" { programmatic string }";
     *     s:6:"fields";a:1:{i:0;s:8:" { field-name string } ";} // OPTIONAL
     *   }
     * }
     *
     * On error, extract each error as an element in data[ 'errors' ]
     * If fields is populated, each field will get its own fully formed element
     * with message and errorCode
     *
     * @param array $raw_data Raw data array describing errors
     * @return array Indexed array of errors
     */
    protected function extractDataErrors( $raw_data ) {

        // Set default values
        $default_error = array( 'message' => '', 'errorCode' => '', 'field' => '' );

        foreach ( $raw_data as $single_error ) {

            $temp = array_merge( $default_error, $single_error );

            if ( is_array( $single_error[ 'fields' ] ) ) {

                $extracted_errors = $this->iterateErrorFields( $temp );
            } else {

                $extracted_errors = array( $temp );
            }
        }

        return $extracted_errors;
    }

    /**
     * Splits a single error with multiple fields into individual errors
     *
     * Each individual error has a string as an error
     *
     * @param array $single_error Single error from raw response data
     * @return array Indexed array of errors
     */
    protected function iterateErrorFields( $single_error ) {

        $field_list = $single_error[ 'fields' ];

        unset( $single_error[ 'fields' ] );

        $default_error = array( 'message' => '', 'errorCode' => '' );

        $temp = array_merge( $default_error, $single_error );

        $extracted_errors = array();

        foreach ( $field_list as $field ) {

            $extracted_errors[] = array_merge( $temp, array( 'field' => $field ) );
        }

        return $extracted_errors;
    }

    /**
     * Initialize variables for new analysis
     */
    protected function initVars() {

        $this->full_analysis = array(
            'id' => '', // comes from data on success
            'success' => false, // comes from data on success
            'errors' => array(), // manually extracted on fail
            'date' => '', // from header
            'location' => '' // from header
        );

        if ( isset( $this->raw_response[ 'http_response' ] ) ) {

            $this->http_response = $this->raw_response[ 'http_response' ];
        } else {

            $this->http_response = false;
        }
    }

    /**
     * Sets the incoming response
     * @param array $raw_response
     * @param string Context of response, 'create' or 'getrecords'
     */
    public function setResponse( $raw_response, $context = 'create' ) {

        $this->raw_response = $raw_response;

        $this->context = $context;
    }

    /**
     * Returns the Analysis of the full response
     * @return array
     */
    public function getResponseAnalysis(){

        return $this->full_analysis;
    }
}
