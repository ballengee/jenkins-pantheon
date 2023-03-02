<?php

/**
 * 
 *
 * @author Stuart Sequeira
 */
class SalesforceBuildRequestV2_9 {

    protected $field_array; // incoming field array from $ninja_forms_processing->get_all_fields()
    protected $unprioritized_request_array;
    // set the request array[object_name][field_name]=user_value without any priority until fully iterated
    protected $object_order_array; // an array that specifies the order in which objects must be inserted
    protected $request_array; // the full array of objects and fields sorted in the order needed for processing
    protected $duplicate_check_array; // array(
    //          [object_name] =>array( 
    //              array(
    //                  'salesforce_field' => xx,
    //                  'user_value' => xx
    //              )
    //          )
    //               to be checked for duplicates
    protected $object_request_list; // a list array of objects that are to be requested
    protected $child_object_array; // specifies the child objects and the child object field that uses the parent object id for linking

    function __construct($field_array, $deprecated = true) {

        $this->field_array = $field_array;

        $this->build_object_order();

        $this->build_child_object_array();

        if ($deprecated) {

            $this->iterate_field_array();
        } else {
            $this->iterate_nf3_array(); // Iterate 3.0 array
        }

        if (!$this->validate_unprioritized_request_array()) {

            return false;
        }

        $this->reorder_request_array();

        $this->build_object_request_list();
    }

// Public Methods

    /*
     * Receives a newly created object and its id.  Searches the child object
     * array to find child dependencies.  If any children are in the current
     * request array, add the new id in its linking field so that the child
     * object will be linked to the newly created object
     * 
     */
    public function link_child_objects($salesforce_object, $new_record_id) {


        if (isset($this->child_object_array [$salesforce_object]) && is_array($this->child_object_array[$salesforce_object])) {

            foreach ($this->child_object_array[$salesforce_object] as $child_object => $field_link) {

                if (isset($this->request_array[$child_object])) {
                    $this->request_array[$child_object][$field_link] = $new_record_id;
                }
            }
        }
    }

// Internal Methods
    /**
     * Creates a default array of the order in which objects must be posted
     * to ensure that the following objects can be linked to the newly
     * created object's ID
     * 
     */
    protected function build_object_order() {

        $default_object_order_array = array(
            'Account' => '10',
            'Contact' => '20',
            'Lead' => '25',
            'Opportunity' => '30',
            'Task' => '35',
            'Case' => '37',
            'Event' => '40',
            'Note' => '45',
            'Attachment' => '50',
            'CampaignMember' => '50',
        );

        $this->object_order_array = apply_filters('nfsalesforcecrm_filter_object_order', $default_object_order_array);
    }

    /**
     * Creates a default array of parent objects containing an array of
     * child objects and the field name that links the child to the
     * parent.
     * 
     */
    protected function build_child_object_array() {

        $default_child_object_array = array(
            'Account' => array(
                'Contact' => 'AccountId',
                'Opportunity' => 'AccountId',
                'Task' => 'WhatId',
                'Case' => 'AccountId',
                'Event' => 'WhatId',
                'Note' => 'ParentId',
                'Attachment' => 'ParentId',
            ),
            'Contact' => array(
                'Task' => 'WhoId',
                'Case' => 'ContactId',
                'Event' => 'WhoId',
                'Note' => 'ParentId',
                'Attachment' => 'ParentId',
                'CampaignMember' => 'ContactId',
            ),
            'Lead' => array(
                'Task' => 'WhoId',
                'Event' => 'WhoId',
                'Note' => 'ParentId',
                'CampaignMember' => 'LeadId',
            )
        );

        $this->child_object_array = apply_filters('nfsalesforcecrm_filter_child_object_array', $default_child_object_array);
    }

    /**
     * For NF 2.9
     * 
     * Cycle through the field array from ninja_forms_processing
     * Extract the object and field map
     * Calls method to validate form value
     * 
     * Builds the unprioritized request array when finished cycling
     * 
     */
    protected function iterate_field_array() {

        foreach ($this->field_array as $field_id => $raw_form_value) { //cycle through each submitted field
            $field = ninja_forms_get_field_by_id($field_id);

            // Check that field map is set; if not, continue on to next field
            if (!isset($field['data']['nfsalesforcecrm_field_map']) || 'None' == $field['data']['nfsalesforcecrm_field_map']) {
                continue;
            }

            $this->process_field($field, $raw_form_value);
        }
    }

    /**
     * Iterates array structure for NF3 and sends it to process method
     * 
     * The Process Field method is shared with 2.9, so the special instructions
     * are converted one by one into the 2.9 data structure to prevent 2.9
     * installs from breaking
     */
    protected function iterate_nf3_array() {

        foreach ($this->field_array as $field) {

            if ($field['special_instructions'] == 'none') {

                $raw_form_value = $this->strip_html_tags($field['form_field']);
            }else{
                
                $raw_form_value = $field['form_field'];
            }

            $field['data']['nfsalesforcecrm_field_map'] = $field['field_map'];

            $field = $this->structureSpecialInstructions($field);
            
            
            $this->process_field($field, $raw_form_value);
        }
    }

    /**
     * Converts NF3 special instructions into a NF2.9-friendly data structure
     * 
     * Added February 2018 to refactor for simplifying structure
     * 
     * @param array $field
     * @return array
     */
    protected function structureSpecialInstructions($field) {

        $lookup = array(
            'DuplicateCheck' => 'nfsalesforcecrm_duplicate_check',
            'DateInterval' => 'nfsalesforcecrm_date_interval',
            'DateFormat' => 'nfsalesforcecrm_date_format',
            'ForceBoolean' => 'nfsalesforcecrm_force_boolean',
            'KeepCharacters' => 'nfsalesforcecrm_keep_characters',
            'SalesforceCurrency'=>'nfsalesforcecrm_currency_format',
        );

        // initialize each data key to 0
        foreach (array_values($lookup) as $data_key) {

            $field['data'][$data_key] = 0;
        }

        $field['type'] = 'default';

        $special_instructions = $field['special_instructions'];

        if (in_array($special_instructions, array_keys($lookup))) {

            $data_key = $lookup[$special_instructions];
            $field['data'][$data_key] = 1;
        }

        if ('FileUpload' == $special_instructions) {

            $field['type'] = '_upload';
        }

        return $field;
    }

    /**
     * Adds field to request array, given field data and raw form value
     * 
     * @param array $field field data structured as pre-3
     * @param mixed $raw_form_value Submitted form value
     * 
     */
    protected function process_field($field, $raw_form_value) {

        // extract ojbect and field
        $map_args = $this->extract_object_and_field_map($field['data']['nfsalesforcecrm_field_map']);

        $object = $map_args['object'];

        $salesforce_field = $map_args['salesforce_field'];

        // set field args
        $field_args = $this->extract_field_settings($field);


        // validate form value
        $validated_form_value = $this->validate_raw_form_value($raw_form_value, $field_args);

        $this->unprioritized_request_array[$object][$salesforce_field] = $validated_form_value;

        // Check that duplicate field check is set to true; if not, continue on to next field
        if (!isset($field['data']['nfsalesforcecrm_duplicate_check']) ||
                0 == $field['data']['nfsalesforcecrm_duplicate_check']) {
            return; // was continue when in a single method
        }

        /*
         * NOTE: duplicate check is built as an array of arrays so that
         * multiple matches could be added in the future if needed
         * 
         */
        $this->duplicate_check_array[$object][] = array(
            'salesforce_field' => $salesforce_field,
            'user_value' => $validated_form_value
        );
    }

    /**
     * Converts array values to comma separated string
     * Converts date interval into date set to the interval from the form
     * submission date
     * 
     * @param type $raw_form_value
     * @param type $field_args
     * @return type
     * 
     */
    protected function validate_raw_form_value($raw_form_value, $field_args) {

        $validated_form_value = $raw_form_value;

        // convert array to comma separated string
        if (is_array($validated_form_value) && $field_args['type'] != '_upload') {

            $validated_form_value = implode(',', $validated_form_value);
        }

        switch ($field_args['type']) {

            case '_upload':

                if ('POST3' != NFSALESFORCECRM_MODE) {
                    $file_array = reset($raw_form_value); // get the first keyed array
                    if (isset($file_array['file_url'])) {
                        $filename = $file_array['file_url'];
                        $contents = file_get_contents($filename);
                        $validated_form_value = base64_encode($contents);
                    }
                } else {
                    /*
                     * Extract contents from href link
                     */
                    $contents = nfsalesforcecrm_extract_upload_contents_v2_9($raw_form_value);

                    if ($contents) {
                        $validated_form_value = base64_encode($contents);
                    }
                }
                break;

            default:
        }


        if (isset($field_args['date_interval']) && 1 == $field_args['date_interval']) {

            $date = new DateTime(); // get a timestamp

            $date_format = apply_filters('nfsalesforcecrm_filter_date_interval_format', 'Y-m-d');
            date_add($date, date_interval_create_from_date_string($validated_form_value));

            $validated_form_value = $date->format($date_format);
        }

        if (isset($field_args['date_format']) && 1 == $field_args['date_format']) {

            $date_format = apply_filters('nfsalesforcecrm_filter_date_interval_format', 'Y-m-d');

            $original_date = strtotime($raw_form_value);

            $validated_form_value = date($date_format, $original_date);
        }


        if (isset($field_args['force_boolean']) && 1 == $field_args['force_boolean']) {

            $temp_value = $raw_form_value;

            $false_values = apply_filters('nfsalesforcecrm_boolean_false_values', array(
                '0',
                'false',
                'FALSE',
                'unchecked'
            ));

            if (in_array($temp_value, $false_values)) {
                $temp_value = FALSE;
            }else{
                $temp_value = TRUE;
            }

            $validated_form_value = boolval($temp_value);
        }

        /*
         * Remove any html tags but keep the special characters like apostrophe
         * and ampersand
         */
        if (isset($field_args['keep_characters']) && 1 == $field_args['keep_characters']) {

            $temp_value = $raw_form_value;
            
            $decoded = html_entity_decode($raw_form_value);
            $stripped = wp_strip_all_tags($decoded);
            $test_stripped = $stripped;
            
            $validated_form_value = $temp_value;
        }    
               
        if (isset($field_args['currency_format']) && 1 == $field_args['currency_format']){
            
            $validated_form_value = $this->formatSalesforceCurrency($validated_form_value);
        }
        
        return $validated_form_value;
    }

    /**
     * Converts incoming field into Salesforce currency format (integer)
     * 
     * Added February 2018
     * @param mixed $value_in
     * @return integer
     */
    protected function formatSalesforceCurrency($value_in) {

        if (is_array($value_in)) {

            $value_in = 0;
        }

        $stripped = preg_replace('/[^0-9.]*/', "", $value_in);

        $value_out = intval($stripped);

        return $value_out;
        ;
    }

    /**
     * Check if unprioritized request array has any values before proceeding
     * If not, update comm data and return false to halt processing of form
     * 
     */
    protected function validate_unprioritized_request_array() {

        $build_request_array = array();
        if (!isset($this->unprioritized_request_array) || empty($this->unprioritized_request_array)) {

            $build_request_array['debug']['build_request'][] = array(
                'heading' => 'Form Design Issue:',
                'value' => 'No fields were selected to map to Salesforce in the most recent request'
            );
            $build_request_array['status'][] = __('No fields were submitted in the last request', 'ninja-forms-salesforce-crm');

            nfsalesforcecrm_update_comm_data_v2_9($build_request_array);

            return false;
        }
        return true;
    }

    /**
     * Sorts the unprioritized array into order needed for processing
     * Done by adding the two-digit object order to the front of each
     * object, sorting on the object key, then removing the object order
     * 
     */
    protected function reorder_request_array() {

        foreach ($this->unprioritized_request_array as $object => $array) {

            if (array_key_exists($object, $this->object_order_array)) {

                $temp_object = $this->object_order_array[$object] . $object;
            } else {
                $temp_object = '99' . $object;
            }

            $temp_array[$temp_object] = $array;
        }

        ksort($temp_array);

        foreach ($temp_array as $object => $array) {

            $stripped_object = substr($object, 2);

            $this->request_array[$stripped_object] = $array;
        }
    }

    /**
     * Builds a list of the objects from the prioritized list for iteration
     * This is done so that after each new record added, the request array
     * can be modified to insert the newly created object id for linking
     * 
     */
    protected function build_object_request_list() {

        $this->object_request_list = array_keys($this->request_array);
    }

    /**
     * Extract the field arguments needed for validating each field
     * Set a default value for any field argument that may not be automatically set,
     * especially custom settings created by this extension
     * 
     * Currency field_arg added Feb 2018
     * Removed unneeded $field variable mods
     * 
     * @param array $field
     * @return array
     * 
     */
    protected function extract_field_settings($field) {

        $field_args['type'] = $field['type'];

        /*
         * Boolean to calculate as a date interval
         */
        if (isset($field['data']['nfsalesforcecrm_date_interval'])) {

            $field_args['date_interval'] = $field['data']['nfsalesforcecrm_date_interval'];
        } else {


//            $field['data']['nfsalesforcecrm_date_interval'] = 0;
        }

        /*
         * Boolean to format as date per Salesforce API requirements
         */
        if (isset($field['data']['nfsalesforcecrm_date_format'])) {

            $field_args['date_format'] = $field['data']['nfsalesforcecrm_date_format'];
        } else {

//            $field['data']['date_format'] = 0;
        }

        /*
         * Boolean to format as boolean per Salesforce API requirements
         */
        if (isset($field['data']['nfsalesforcecrm_force_boolean'])) {

            $field_args['force_boolean'] = $field['data']['nfsalesforcecrm_force_boolean'];
        } else {

//            $field['data']['force_boolean'] = 0;
        }

        /*
         * Boolean to preserve special characters
         */
        if (isset($field['data']['nfsalesforcecrm_keep_characters'])) {

            $field_args['keep_characters'] = $field['data']['nfsalesforcecrm_keep_characters'];
        } else {

//            $field['data']['keep_characters'] = 0;
        }
        
        
        return $field_args;
    }

    /**
     * Receives a string of the map argument set by the field registration
     * 
     * Explodes the map argument into a salesforce object and a salesforce field
     * 
     * @param string $map_args
     * @return array
     * 
     */
    protected function extract_object_and_field_map($map_args) {

        $exploded_map_args = explode('.', $map_args);

        $object = $exploded_map_args[0];

        $salesforce_field = $exploded_map_args[1];


        $return_array = array(
            'object' => $object,
            'salesforce_field' => $salesforce_field
        );

        return $return_array;
    }

    /**
     * Strips the HTML tags 
     * 
     * NF3 escapes textarea tags; when these get sent to a custom field, these
     * tags appear as text.  The new default function is to decode
     * and then strip these tags, then escape the result
     * 
     * A filter allows one to keep the new functionality
     * @param type $raw_form_value
     */
    protected function strip_html_tags($raw_form_value) {

        $form_value = $raw_form_value; // initialize

        $keep_tags = apply_filters('nfsalesforcecrm_keep_html_tags', FALSE);

        if (!$keep_tags) {
            $decoded = html_entity_decode($raw_form_value);
            $stripped = wp_strip_all_tags($decoded);
            $form_value = esc_html($stripped);
        }

        return $form_value;
    }

// Gets and Sets

    public function get_request_array() {

        if (!isset($this->request_array) || !is_array($this->request_array)) {

            return false;
        } else {

            return $this->request_array;
        }
    }

    public function get_duplicate_check_array() {

        if (!isset($this->duplicate_check_array) || !is_array($this->duplicate_check_array)) {

            return false;
        } else {

            return $this->duplicate_check_array;
        }
    }

    public function get_object_request_list() {


        if (!isset($this->object_request_list) || !is_array($this->object_request_list)) {

            return false;
        } else {

            return $this->object_request_list;
        }
    }

    public function get_object_field_list($object) {

        if (!isset($this->request_array [$object]) || !is_array($this->request_array[$object])) {

            return false;
        } else {

            return $this->request_array[$object];
        }
    }

}
