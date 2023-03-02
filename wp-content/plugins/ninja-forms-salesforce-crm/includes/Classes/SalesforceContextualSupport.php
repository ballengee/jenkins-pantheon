<?php

/**
 * Given context, search help configuration and returns contextual help
 *
 */
class SalesforceContextualSupport {

    /**
     * Array of configured contextual help
     *
     * @var array
     */
    protected $help_config_array = array();

    /**
     * Error 'message' key of error response
     *
     * @var string
     */
    protected $message;

    /**
     * Error 'errorCode' key of error response
     * @var string
     */
    protected $error_code;

    /**
     * 'field' key of error response
     * @var string
     */
    protected $field;

    public function __construct() {

        $this->help_config_array = apply_filters( 'nfsalesforcecrm_contextual_help', NF_SalesforceCRM::config( 'ContextualHelp' ) );
    }

    /**
     * Searches for context in help configuration and returns array of help text
     *
     * Context should be set before using this method
     *
     * @return array
     */
    public function searchContext() {

        $contextual_help_array = array();

        foreach ( $this->help_config_array as $search_string => $help_array ) {

            if (!$this->isMatched( $search_string ) ) {

                continue;
            }

            if ( empty( $contextual_help_array ) ) {

                $contextual_help_array = $help_array[ 'help_text' ];
            } else {

                $contextual_help_array = array_push( $contextual_help_array, $help_array[ 'help_text' ] );
            }

            foreach ( $help_array[ 'append' ] as $key ) {

                $contextual_help_array[] = $this->$key;
            }
        }

        if ( empty( $contextual_help_array ) ) {

            $default_help = $this->error_code . ': ' . $this->message;

            $contextual_help_array = array( $default_help );
        }

        return $contextual_help_array;
    }

    /**
     * Given an code or message snippet, determines if Contextual Help exists
     *
     * @param string $search_string Portion of string to be matched
     * @return boolean
     */
    protected function isMatched( $search_string ) {

        $is_matched = false;

        if (!( stripos( $this->error_code, $search_string ) === false) ) {

            $is_matched = true;
        }

        if ( !(stripos( $this->message, $search_string ) === false )) {

            $is_matched = true;
        }

        return $is_matched;
    }

    /**
     * Sets context and returns contextual help array
     *
     * @param array error Message from response error
     * @return array
     */
    public function setAndSearchContext( $message, $error_code, $field ) {

        $this->setContext( $message, $error_code, $field );

        $contextual_help_array = $this->searchContext();

        return $contextual_help_array;
    }

    /**
     * Set context of what support is needed
     *
     * A string is given and it is used to search for available help
     * documentation.
     * @param string $context
     */
    public function setContext( $message, $error_code, $field ) {

        $this->message = $message;

        $this->error_code = $error_code;

        $this->field = $field;
    }

}
