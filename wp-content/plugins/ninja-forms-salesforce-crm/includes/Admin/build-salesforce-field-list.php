<?php

/**
 * Builds the drop-down list of Salesforce fields that can be mapped to
 * 
 * Uses the global account_data variable to construct list.  Structure is
 * an index array of 'name'-'value' pairs.  The 'name' is reader-friendly,
 * the 'value' is programmatic
 * 
 * @global array $nfsalesforcecrm_account_data
 * @return array
 * 
 * TODO: refactor this function to eliminate multiple return statements
 */
function nfsalesforcecrm_build_salesforce_field_list() {

    global $nfsalesforcecrm_account_data;

    $default_fields_array = array(
        array(
            'name' => 'None',
            'value' => 'None'
        )
    );

    if ( !isset( $nfsalesforcecrm_account_data[ 'field_list' ] ) || !is_array( $nfsalesforcecrm_account_data[ 'field_list' ] ) ) {

        return $default_fields_array;
    }

    $iterating_array = array();

    foreach ( $nfsalesforcecrm_account_data[ 'field_list' ] as $object => $field_name_label_pair ) {

        foreach ( $field_name_label_pair as $name => $label ) {

            $iterating_array[] = array(
                'name' => $object . ' - ' . $label,
                'value' => $object . '.' . $name
            );
        }
    }

    if ( !empty( $iterating_array ) ) {

        $salesforce_fields_array = array_merge( $default_fields_array, $iterating_array );
    } else {

        $salesforce_fields_array = $default_fields_array;
    }

    return $salesforce_fields_array;
}
