<?php

add_filter('nfsalesforcecrm_filter_mappable_extension_field_types','nfsalesforcecrm_add_ua_extension_to_mappable_fields');
/* 
 * Use array_merge to add an array of new field types from extensions to add 
 * 
 */
function nfsalesforcecrm_add_ua_extension_to_mappable_fields( $current_array) {
    
    $updated_array = $current_array;
    
    global $NF_User_Analytics;

    if ( isset( $NF_User_Analytics ) ) {

        $ua_fields = $NF_User_Analytics->get_ua_fields(); // access the get_ua_fields() method of the UA class

        $updated_array = array_merge( $current_array, array_keys( $ua_fields ) );
    }

    return $updated_array;
}