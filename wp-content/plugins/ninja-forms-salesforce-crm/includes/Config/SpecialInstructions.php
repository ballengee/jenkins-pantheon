<?php

if ( !defined( 'ABSPATH' ) )
    exit;

return array(
    array(
        'label' => __( '-None-', 'ninja-forms-salesforce-crm' ),
        'value' => 'none',
    ),
    array(
        'label' => __( 'Check for duplicates in this field', 'ninja-forms-salesforce-crm' ),
        'value' => 'DuplicateCheck'
    ),
    array(
        'label' => __( 'This is a date interval (ex: 2 days)', 'ninja-forms-salesforce-crm' ),
        'value' => 'DateInterval'
    ),
    array(
        'label' => __( 'Format Date for Salesforce', 'ninja-forms-salesforce-crm' ),
        'value' => 'DateFormat'
    ),
    array(
        'label' => __( 'File Upload', 'ninja-forms-salesforce-crm' ),
        'value' => 'FileUpload'
    ),
    array(
        'label' => __( 'Format for true/false', 'ninja-forms-salesforce-crm' ),
        'value' => 'ForceBoolean'
    ),
    array(
        'label' => __( 'Keep ampersands and quotes', 'ninja-forms-salesforce-crm' ),
        'value' => 'KeepCharacters'
    ),
    /**
     * @since 3.1.0
     * support ticket KustomerEvent 5a57f1c9b573fd00012990ac
     */
    array(
        'label' => __( 'Format for Currency', 'ninja-forms-salesforce-crm' ),
        'value' => 'SalesforceCurrency'
    ),
    /**
     * @since 3.1.0
     */
    array(
        'label' => __( 'Separate selections with semi-colon', 'ninja-forms-salesforce-crm' ),
        'value' => 'SemicolonDelimiter'
    ),
);


