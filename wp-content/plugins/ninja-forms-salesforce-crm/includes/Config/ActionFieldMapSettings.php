<?php

if ( !defined( 'ABSPATH' ) )
    exit;

return array(
    /*
      |--------------------------------------------------------------------------
      | Salesforce Field Map
      |--------------------------------------------------------------------------
     */
    'field_map' => array(
        'name' => 'salesforce_field_map',
        'type' => 'option-repeater',
        'label' => __( 'Salesforce Field Map', 'ninja-forms-salesforce-crm' ) . ' <a href="#" class="nf-add-new">' . __( 'Add New' ) . '</a>',
        'width' => 'full',
        'group' => 'primary',
        'tmpl_row' => 'nf-tmpl-salesforce-custom-field-map-row',
        'value' => array(),
        'columns' => array(
            'form_field' => array(
                'header' => __( 'Form Field', 'ninja-forms-salesforce-crm' ),
                'default' => '',
                'options' => array()
            ),
            'field_map' => array(
                'header' => __( 'Salesforce Field', 'ninja-forms-salesforce-crm' ),
                'default' => '',
                'options' => array(), // created on constuction
            ),
            'special_instructions' => array(
                'header' => __( 'Data Handling Instructions', 'ninja-forms-salesforce-crm' ),
                'default' => '',
                'options' => array() // created on construction
            ),
        ),
    ),
);


