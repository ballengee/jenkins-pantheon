<?php

/*
 * Builds the drop-down list of Salesforce fields that can be mapped to
 * 
 */

function nfsalesforcecrm_build_salesforce_field_list_v2_9() {

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
