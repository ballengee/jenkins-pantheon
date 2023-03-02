<?php

/*
 * Process the submitted form to send the data to Salesforce
 * 
 */

function nfsalesforcecrm_process_form_to_insert_form_data() {

    global $ninja_forms_processing;

    $new_object_array = array();

    $the_droids = $ninja_forms_processing->get_form_setting( 'nfsalesforcecrm_send_to_salesforce' );

    if ( !$the_droids ) {
        return;
    } //these are not the droids you're looking for

    $api_parameter_array = nfsalesforcecrm_retrieve_api_parameters_v2_9();

    if ( !$api_parameter_array ) {
        return false;
    }// unsuccessful getting parameter array //may move this to process_object_list function


    $field_array = $ninja_forms_processing->get_all_fields();

    $request_object = new SalesforceBuildRequestV2_9( $field_array );

    $object_request_list = $request_object->get_object_request_list();

    if ( !$object_request_list ) {
        return false;
    }

    nfsalesforcecrm_process_object_list_v2_9($object_request_list, $request_object, $api_parameter_array);
}


