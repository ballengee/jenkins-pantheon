<?php

/**
 * For each field in the form design, add the field map option
 * after checking if the given field type is mappable
 * @param type $field_id
 * @return none
 * 
 */
add_action( 'ninja_forms_edit_field_after_registered', 'nfsalesforcecrm_add_field_map_option', 10 );
function nfsalesforcecrm_add_field_map_option( $field_id ) {

    // Get the field type for the given field
    $field = ninja_forms_get_field_by_id( $field_id );
    $field_type = $field[ 'type' ];

    $mappable_field_types = nfsalesforcecrm_mappable_field_types();

    if ( !in_array( $field_type, $mappable_field_types ) ) {

        return;  // the field type is not one to map
    }

    // check if this field has a field map already selected
    if ( isset( $field[ 'data' ][ 'nfsalesforcecrm_field_map' ] ) ) {

        $value = $field[ 'data' ][ 'nfsalesforcecrm_field_map' ];
    } else {

        $value = 'none';
    }//end is set

    $options = nfsalesforcecrm_build_salesforce_field_list_v2_9(); // the drop-down list of available fields in Salesforce
    // Add field mapping option to the field
    ninja_forms_edit_field_el_output(
            $field_id, 'select', // type
            __( 'Salesforce CRM Field Map', 'ninja-forms-salesforce-crm' ), // label
            'nfsalesforcecrm_field_map', // name
            $value, 'wide', // width
            $options, 'widefat', // class
            __( 'Select which Salesforce CRM field to send this form field', 'ninja-forms-salesforce-crm' ), // description
            '' // label class
    );

    return;
}

/**
 * For each field in the form design, add a checkbox to do a check for
 * duplicate values submitted in the given field
 * after checking if the given field type is mappable
 * @param type $field_id
 * @return none
 * 
 */
add_action( 'ninja_forms_edit_field_after_registered', 'nfsalesforcecrm_add_duplicate_check_checkbox', 10 );
function nfsalesforcecrm_add_duplicate_check_checkbox( $field_id ) {

    // Get the field type for the given field
    $field = ninja_forms_get_field_by_id( $field_id );
    $field_type = $field[ 'type' ];

    $mappable_field_types = nfsalesforcecrm_mappable_field_types();

    if ( !in_array( $field_type, $mappable_field_types ) ) {

        return;  // the field type is not one to map
    }

    // check if this field has been set for duplicate check or not
    if ( isset( $field[ 'data' ][ 'nfsalesforcecrm_duplicate_check' ] ) ) {

        $value = $field[ 'data' ][ 'nfsalesforcecrm_duplicate_check' ];
    } else {

        $value = 0;
    }//end is set
    // Add field mapping option to the field
    ninja_forms_edit_field_el_output(
            $field_id, 'checkbox', // type
            __( 'Check for duplicates in this Salesforce Field?', 'ninja-forms-salesforce-crm' ), // label
            'nfsalesforcecrm_duplicate_check', // programatic name
            $value, 'wide', // width
            '', 'widefat', // class
            '', // description
            '' // label class
    );

    return;
}

/**
 * For each field in the form design, add the date interval checkbox
 * after checking if the given field type is mappable
 * @param type $field_id
 * @return none
 * 
 */
add_action( 'ninja_forms_edit_field_after_registered', 'nfsalesforcecrm_add_date_interval_option', 10 );
function nfsalesforcecrm_add_date_interval_option( $field_id ) {

    // Get the field type for the given field
    $field = ninja_forms_get_field_by_id( $field_id );
    $field_type = $field[ 'type' ];

    $mappable_field_types = nfsalesforcecrm_mappable_field_types();

    if ( !in_array( $field_type, $mappable_field_types ) ) {

        return;  // the field type is not one to map
    }

    // check if this field has a field map already selected
    if ( isset( $field[ 'data' ][ 'nfsalesforcecrm_date_interval' ] ) ) {

        $value = $field[ 'data' ][ 'nfsalesforcecrm_date_interval' ];
    } else {

        $value = 0;
    }//end is set

    $options = nfsalesforcecrm_build_salesforce_field_list_v2_9();

    // Add field mapping option to the field
    ninja_forms_edit_field_el_output(
            $field_id, 'checkbox', __( 'Calculate date INTERVAL (ex. "2 days from now)"  in Salesforce?', 'ninja-forms-salesforce-crm' ), // label
            'nfsalesforcecrm_date_interval', // programmatic name
            $value, // value
            'wide', // width
            $options, // option array
            'widefat', // class
            '', // description
            '' // label class
    );

    return;
}

/**
 * For each field in the form design, add the date interval checkbox
 * after checking if the given field type is mappable
 * @param type $field_id
 * @return none
 * 
 */
add_action( 'ninja_forms_edit_field_after_registered', 'nfsalesforcecrm_add_format_as_date_option', 10 );
function nfsalesforcecrm_add_format_as_date_option( $field_id ) {

    // Get the field type for the given field
    $field = ninja_forms_get_field_by_id( $field_id );
    $field_type = $field[ 'type' ];

    $mappable_field_types = nfsalesforcecrm_mappable_field_types();

    if ( !in_array( $field_type, $mappable_field_types ) ) {

        return;  // the field type is not one to map
    }

    // check if this field has a field map already selected
    if ( isset( $field[ 'data' ][ 'nfsalesforcecrm_date_format' ] ) ) {

        $value = $field[ 'data' ][ 'nfsalesforcecrm_date_format' ];
    } else {

        $value = 0;
    }//end is set

    $options = nfsalesforcecrm_build_salesforce_field_list_v2_9();

    // Add field mapping option to the field
    ninja_forms_edit_field_el_output(
            $field_id, 'checkbox', __( 'This is a date field in Salesforce', 'ninja-forms-salesforce-crm' ), // label
            'nfsalesforcecrm_date_format', // programmatic name
            $value, // value 
            'wide', // width
            $options, // option array
            'widefat', // class
            '', // description
            '' // label class
    );

    return;
}

/**
 * Build a list of standard Ninja Form fields that are mappable
 * Enable via filter a way to add fields created by extensions
 * 
 * @return array
 * 
 */
function nfsalesforcecrm_mappable_field_types() {

    $standard_mappable_field_types = array(
        '_text',
        '_hidden',
        '_list',
        '_checkbox',
        '_textarea',
        '_number',
        '_calc',
        '_state',
        '_country',
        '_tax',
        '_upload'
    );

    $mappable_extension_field_types = apply_filters( 'nfsalesforcecrm_filter_mappable_extension_field_types', array() );

    if ( empty( $mappable_extension_field_types ) ) {

        $mappable_field_types = $standard_mappable_field_types;
    } else {

        $mappable_field_types = array_merge( $standard_mappable_field_types, $mappable_extension_field_types );
    }

    return $mappable_field_types;
}
