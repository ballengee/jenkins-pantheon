<?php

/**
 * Returns array of contextual help data
 *
 * [search_string] =>array(
 *      'help_text'=>
 *          [help text]
 *          [help text]
 *      'append'=>
 *          [error_code] (OPTIONAL)
 *          [message] (OPTIONAL)
 *          [field] (OPTIONAL)
 */
return apply_filters( 'nfsalesforcecrm_filter_contextual_help', array(
    // String too long
    'STRING_TOO_LONG' => array(
        'help_text' => array(
            __( 'Your text field value is too long for the Salesforce field', 'ninja-forms-salesforce-crm' ),
            __( 'Please limit your Ninja Forms input field the maximum number specified below.', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'message'
        ),
    ),
    // Required field is missing
    'REQUIRED_FIELD_MISSING' => array(
        'help_text' => array(
            __( 'A field required by Salesforce is missing in the submission', 'ninja-forms-salesforce-crm' ),
            __( 'Please ensure:', 'ninja-forms-salesforce-crm' ),
            __( '1. You have field mapped a value to the required field', 'ninja-forms-salesforce-crm' ),
            __( '2. Your form requires that a value be entered for that field', 'ninja-forms-salesforce-crm' ),
            __( 'Here is the field with issues:', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'field'
        ),
    // Currency field is incorrect format
    ),
    'Cannot deserialize instance of currency' => array(
        'help_text' => array(
            __( 'The submission is sending a non-number to a currency field.', 'ninja-forms-salesforce-crm' ),
            __( '1. Find the submission value specified in the "Cannot deserialize instance of currency" line below', 'ninja-forms-salesforce-crm' ),
            __( '2. Find the Salesforce Field Name getting that value.', 'ninja-forms-salesforce-crm' ),
            __( '3. Go to your Add to Salesforce field map and find that field.', 'ninja-forms-salesforce-crm' ),
            __( '4. Select "Format for Salesforce Currency" in the special instruction column for that field', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'message',
        ),
    ),
    // Wrong data type for integer
    'Cannot deserialize instance of int' => array(
        'help_text' => array(
            __( 'The submission is sending a non-integer value to a field requiring an integer', 'ninja-forms-salesforce-crm' ),
            __( '1. Find the submission value specified in the "Cannot deserialize instance of int" line below', 'ninja-forms-salesforce-crm' ),
            __( '2. Find the Salesforce Field getting that value.', 'ninja-forms-salesforce-crm' ),
            __( '3. In your form design, change that field to a Ninja Forms Number field type, or use a mask.', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'message',
        ),
    ),
    // Wrong data type for date
    'Cannot deserialize instance of date' => array(
        'help_text' => array(
            __( 'The submission is sending a non-date value to a field requiring a date', 'ninja-forms-salesforce-crm' ),
            __( '1. Find the submission value specified in the "Cannot deserialize instance of date" line below', 'ninja-forms-salesforce-crm' ),
            __( '2. Find the Salesforce Field getting that value.', 'ninja-forms-salesforce-crm' ),
            __( '3. In your form design, change that field to a Ninja Forms Date field type, or use a mask.', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'message',
        ),
    ),
    // Incorrect structure for email address
    'INVALID_EMAIL_ADDRESS' => array(
        'help_text' => array(
            __( 'The submission is sending a non-email value to an email field', 'ninja-forms-salesforce-crm' ),
            __( '1. Find the submission value sending the non-email value specifid below', 'ninja-forms-salesforce-crm' ),
            __( '2. Find the Salesforce Field getting that value.', 'ninja-forms-salesforce-crm' ),
            __( '3. In your form design, change that field to an Email field type.', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'message',
        ),
    ),
    // Invalid value for a record ID
    'MALFORMED_ID' => array(
        'help_text' => array(
            __( 'An incorrect value is being sent to an ID field (a field that identifies a record in your Salesforce Account)', 'ninja-forms-salesforce-crm' ),
            __( 'If you are creating multiple objects in a single form, check that all objects above this one in the list are corrected first.', 'ninja-forms-salesforce-crm' ),
            __( 'If you are manually adding an ID, ensure that you have the correct value for the ID.  You may need to check with your Salesforce rep.', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(
            'message',
        ),
    ),
    // WP error - no internet connection
    'WordPress had an internal error' => array(
        'help_text' => array(
            __( 'Your website had an error trying to communicate with Salesforce', 'ninja-forms-salesforce-crm' ),
            __( 'This can occur if you do not have an internet connection.  Please check your internet connection', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(),
    ),
    // Key, Secret, Refresh Token combination is not valid
    'invalid_client' => array(
        'help_text' => array(
            __( 'Your Consumer Key + Secret + Token do not match', 'ninja-forms-salesforce-crm' ),
            __( '1. Please check your key and secret', 'ninja-forms-salesforce-crm' ),
            __( '2. Regenerate an Authorization Code and SAVE your settings', 'ninja-forms-salesforce-crm' ),
            __( '3. Regenerate a new refresh token', 'ninja-forms-salesforce-crm' ),
            __( '4. Try refreshing your objects and fields', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(),
    ),
    // Mapping to an object that isn't in Salesforce
    'NOT_FOUND'=>array(
        'help_text' => array(
            __( 'Your field map is trying to create an object that we cannot find', 'ninja-forms-salesforce-crm' ),
            __( '1. Please check that your Objects to Retrieve setting includes all the objects you want to create.', 'ninja-forms-salesforce-crm' ),
            __( '2. Check your field map to ensure every line has a Salesforce Field value selected.', 'ninja-forms-salesforce-crm' ),
        ),
        'append' => array(),
    ),
        ) );
