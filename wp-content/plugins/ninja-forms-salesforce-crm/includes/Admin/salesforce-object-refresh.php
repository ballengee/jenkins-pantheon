<?php

/*
 * Process the submitted form to send the data to Salesforce
 * 
 */

function nfsalesforcecrm_refresh_salesforce_objects() {

    global $nfsalesforcecrm_settings;

    $api_parameter_array = nfsalesforcecrm_retrieve_api_parameters();

    if (!$api_parameter_array) {

        return false; // unsuccessful getting parameter array
    }

    $new_salesforce_account_data['version_url'] = $api_parameter_array['version_url'];

    /*
     * Create the List of Objects object
     * 
     */
    $list_of_objects_object = new SalesforceListOfObjects($api_parameter_array);
    $list_of_objects_result = $list_of_objects_object->get_result();
    $list_of_objects_temp_data = $list_of_objects_object->get_comm_data();

    $refresh_object_comm_data['debug'][] = $list_of_objects_temp_data['debug'];
    $refresh_object_comm_data['status'][] = $list_of_objects_temp_data['status'];


    if ('success' != $list_of_objects_result) {

        nfsalesforcecrm_update_comm_data($refresh_object_comm_data);
        return false;
    }

    $list_of_objects = $list_of_objects_object->get_list_of_objects();
    $new_salesforce_account_data['object_list'] = ( $list_of_objects );


    /*
     * Cycle through each object specified in settings and 
     * create a Field List for each object
     * 
     */
    if (isset($nfsalesforcecrm_settings['nfsalesforcecrm_available_objects']) && 0 < strlen($nfsalesforcecrm_settings['nfsalesforcecrm_available_objects'])) {

        $available_object_array = explode(',', $nfsalesforcecrm_settings['nfsalesforcecrm_available_objects']);
    } else {

        $available_object_array = array('Lead');
    }

    /*
     * Cycle through each object in the list to retrieve the field list
     * 
     */
    foreach ($available_object_array as $untrimmed_object) {

        $object_name = trim($untrimmed_object);

        // add object name to parameters
        $object_description_parameters = $api_parameter_array;
        $object_description_parameters['object_name'] = $object_name;

        /*
         * Describe each object; if field is creatable, add it to the list
         */
        $object_description_object = new SalesforceDescribeObject($object_description_parameters);
        $object_description_result = $object_description_object->get_result();
        $object_temp_array = $object_description_object->get_comm_data();

        $refresh_object_comm_data['status'][] = $object_temp_array['status'];

        // if successful getting object description, build field lists, else set error
        if ('success' == $object_description_result) {

            $object_description = $object_description_object->get_object_description();
            unset($object_field_list);
            foreach ($object_description['fields'] as $field_array) {

                if ($field_array['createable']) {
                    $object_field_list[$field_array['name']] = $field_array['label'];
                }
            }

            // check if $object_field_list has values, set field list if yes, else set error 
            if (isset($object_field_list)) {

                $new_salesforce_account_data['field_list'][$object_name] = $object_field_list;
            } else {

                $new_salesforce_account_data['field_list'][$object_name] = array('NoWriteEnabledFields' => 'There are no fields available for mapping');
            }
        } else {
            $new_salesforce_account_data['field_list'][$object_name] = array('UnableToDescribeObject' => 'I was unable to describe this object');
        }
    }


    nfsalesforcecrm_update_comm_data($refresh_object_comm_data);
    nfsalesforcecrm_update_account_data($new_salesforce_account_data);

    if ('POST3' === NFSALESFORCECRM_MODE) {

        wp_redirect(admin_url() . 'admin.php?page=nf-settings#'.NF_SalesforceCRM::BOOKMARK);
        exit;
    }
}
