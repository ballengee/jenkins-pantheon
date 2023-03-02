<?php

if (!defined('ABSPATH'))
    exit;
/**
 * A list of fields to extract from the submitted form
 */
return array(
    'form_field', // the submitted value from the form
    'field_map', // the field to which the data will be added
    'special_instructions',// Drop down of special instructions for the field
    //  'duplicate_check', // check this field for duplicates?
    //  'date_interval', // is this field a date interval?
    //  'date_format', // format this field for Salesforce date format?
);


