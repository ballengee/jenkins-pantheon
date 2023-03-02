<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Retrieve db options as global variables to minimize db calls
 * 
 * @var array $nfsalesforcecrm_settings Client ID, Secret, Authorization Code, and Objects to Refresh
 * @var array $nfsalesforcecrm_comm_data Communication data for support
 * @var array $nfsalesforcecrm_account_data Objects and Fields available for field mapping
 * 
 */
function nfsalesforce_load_globals() {

    /**
     * Array of Salesforce settings entered by user including consumer key,
     * consumer secret, authorization code, and objects to be made available
     * 
     * @var array 
     */
    global $nfsalesforcecrm_settings;

    /**
     * @var array Communication data stored for support 
     */
    global $nfsalesforcecrm_comm_data;

    /**
     * Data retrieved from the account, including the Salesforce version, a list 
     * of the objects and the fields within those objects
     * 
     * @var array 
     */
    global $nfsalesforcecrm_account_data;

        $nfsalesforcecrm_comm_data = get_option('nfsalesforcecrm_comm_data');

    $nfsalesforcecrm_account_data = get_option('nfsalesforcecrm_account_data');
    
    
    
    /*
     * in 3.0 this is stored in db option ninja_forms_settings
     * 
     * available objects is a user entered commma delimited array of the objects
     * to be used to build the field map list.
     */
    $keys_to_extract = array(
        'nfsalesforcecrm_consumer_key',
        'nfsalesforcecrm_consumer_secret',
        'nfsalesforcecrm_authorization_code',
        'nfsalesforcecrm_refresh_token', // not manually entered; stored in nfsalesforcecrm_settings        
        'nfsalesforcecrm_available_objects', // objects TO BE MADE available in the field list
    );


    /*
     * The site-wide settings 
     * 
     * In NF3, the refresh token isn't stored with the other settings because
     * it is not manually entered so it is stored in nfsalesforcecrm_settings
     * 
     */
    $bypassed_settings = get_option('nfsalesforcecrm_settings');

    if ('2.9x' == NFSALESFORCECRM_MODE) {

        $temp_array = $bypassed_settings;
    } else {

        // In a NF 3.0 setup, the settings are all stored in option ninja_forms_settings
        $nf_settings_array = get_option(' ninja_forms_settings');

        foreach ($keys_to_extract as $key) {

            if (isset($nf_settings_array[$key])) {

                $temp_array[$key] = $nf_settings_array[$key];
            } elseif (isset($bypassed_settings[$key])) {

                // If NF3 key isn't set, grab the NF2.9 version
                $temp_array[$key] = $bypassed_settings[$key];
            } else {

                // ensure it is at least set
                $temp_array[$key] = '';
            }
        }
    }
    // set the global
    $nfsalesforcecrm_settings = $temp_array;
}

/**
 * Returns the advanced codes setting field as an array
 * @return array
 */
function nfsalesforcecrm_extract_advanced_codes() {

    $settings_key = 'nfsalesforcecrm_advanced_codes';

    $advanced_codes_array = array(); //initialize
    
    $nf_settings_array = Ninja_Forms()->get_settings();

    if (isset($nf_settings_array[$settings_key])) {

        $advanced_codes_setting = $nf_settings_array[$settings_key];

        $advanced_codes_array = array_map('trim', explode(',', $advanced_codes_setting));
    }

    return $advanced_codes_array;
}

/**
 * Checks if given advanced code is set and returns boolean answer
 * 
 * @param string $needle
 * @return boolean
 */
function nfsalesforcecrm_advanced_code_is_set($needle){
    
    $haystack = nfsalesforcecrm_extract_advanced_codes();
    
    
    if (in_array($needle, $haystack)) {
        $advanced_code_is_set =  true;
    }else{
       $advanced_code_is_set = false; 
    }
    
    return $advanced_code_is_set;
}

/**
 * Create HTML for account data
 * 
 * @global array $nfsalesforcecrm_settings
 * @global array $nfsalesforcecrm_account_data
 * @return type
 * 
 */
function nfsalesforcrcrm_output_account_data() {

//    global $nfsalesforcecrm_settings;
    global $nfsalesforcecrm_account_data;


    if (isset($nfsalesforcecrm_account_data['version'])) {

        $version = $nfsalesforcecrm_account_data['version'];
    } else {
        $version = false;
    }

    if (isset($nfsalesforcecrm_account_data['object_list'])) {
        $object_list = implode(' , ', $nfsalesforcecrm_account_data['object_list']);
    } else {

        $object_list = __('No Salesforce object list currently available', 'ninja-forms-salesforce-crm');
    }

    if (isset($nfsalesforcecrm_account_data['field_list']) && is_array($nfsalesforcecrm_account_data['field_list'])) {
        $field_list = $nfsalesforcecrm_account_data['field_list'];
    } else {
        $field_list = false;
    }

    ob_start();
    ?>
    <table class="form-table">
        <tbody>
            <?php if ($version) { ?>
                <tr valign="top">
                    <th scope="row"><?php _e('Salesforce Version', 'ninja-forms-salesforce-crm'); ?></th>
                    <td>

                        <?php echo($version); ?>

                    </td>
                </tr> 
                <?php
            }
            if ($field_list) {
                foreach ($field_list as $object => $list) {
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php echo $object; ?></th>
                        <td><?php echo implode(' , ', array_keys($list)); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr valign="top">
                <th scope="row"><?php _e('Salesforce Object List', 'ninja-forms-salesforce-crm'); ?></th>
                <td>

                    <?php echo($object_list); ?>

                </td>
            </tr> 

        </tbody>
    </table>    
    <?php
    $account_data_html = ob_get_clean();

    return $account_data_html;
}

/**
 * Update communication data as a nested array for support
 * 
 * 'debug'=>array(
 *      *class* => array(
 *          array(
 *              'heading' => string,
 *              'value' => string
 *          )
 *          . . .
 *      )
 *      . . .
 *  )
 *  'status'=>array(
 *      (string)
 *      . . .
 *  )
 * @param array $comm_data_array
 * 
 */
function nfsalesforcecrm_update_comm_data_v2_9($comm_data_array) {

    update_option('nfsalesforcecrm_comm_data', $comm_data_array);
}

function nfsalesforcecrm_update_account_data($account_data_array) {

    update_option('nfsalesforcecrm_account_data', $account_data_array);
}

function nfsalesforcecrm_update_settings($nfsalesformcrm_settings) {

    update_option('nfsalesforcecrm_settings', $nfsalesformcrm_settings);
}

add_action('init', 'nfsalesforcecrm_listener');

/**
 * Listens for POST or GET requests with specific commands
 * 
 * Calls specific functions based on the request made; uses if statements with
 * a switch/case so that only vetted functions are called instead of allowing
 * for unvetted function calls
 */
function nfsalesforcecrm_listener() {

    /*
     * Trigger added in 3.0 to enable activation both from 2.9's form button,
     * which uses a form button with action
     * and 3.0's refresh token listener
     */
    $trigger = false; // initialize

    if (isset($_POST['action']) && $_POST['action'] == 'nfsalesforcecrm_generate_refresh_token_listener') {
        $trigger = 'refresh_token';

    }

    /*
     * Ensure this GET listener matches the URL in Settings.php
     */
    if (isset($_GET['nfsalesforcecrm_instructions']) && 'refresh_token' == $_GET['nfsalesforcecrm_instructions']) {

        $trigger = 'refresh_token';
    }

    /*
     * Ensure this GET listener matches the URL in Settings.php
     */
    if (isset($_GET['nfsalesforcecrm_instructions']) && 'refresh_objects' == $_GET['nfsalesforcecrm_instructions']) {

        $trigger = 'refresh_objects';
    }
    
    switch($trigger){
        
        case 'refresh_token':
            nfsalesforcecrm_refresh_token_v2_9();
            break;
        case 'refresh_objects':
            nfsalesforcecrm_refresh_salesforce_objects_v2_9();
            break;
        default:
            break;
    }
}
 
/**
 * Attempts to generate refresh token from key, secret, and auth code
 * 
 * @global array $nfsalesforcecrm_settings Salesforce settings array in db
 * 
 */
function nfsalesforcecrm_refresh_token_v2_9(){
        global $nfsalesforcecrm_settings;
    /*
     * In 3.0, the Ninja_Forms setting strips out the character codes,
     * run function to add the missing characters
     */

    $filtered_authorization_code = nfsalesforcecrm_filter_authcode($nfsalesforcecrm_settings['nfsalesforcecrm_authorization_code']);

    $parameter_array = array(
        'client_id' => $nfsalesforcecrm_settings['nfsalesforcecrm_consumer_key'],
        'client_secret' => $nfsalesforcecrm_settings['nfsalesforcecrm_consumer_secret'],
        'authorization_code' => urldecode($filtered_authorization_code)
    );

    $refresh_token_object = new SalesforceRefreshToken($parameter_array);

    $refresh_token = $refresh_token_object->get_refresh_token();
    $nfsalesforcecrm_settings['nfsalesforcecrm_refresh_token'] = $refresh_token;
    $nfsalesforcecrm_settings['nfsalesforcecrm_authorization_code'] = __('Regenerate authorization code only if needed for new refresh token', 'ninja-forms-salesforce-crm');

    // in 3.0, only refresh token is stored in the nfsalesforcecrm_settings
    // in 2.9, both refresh token and now-overwritten auth code are stored
    nfsalesforcecrm_update_settings($nfsalesforcecrm_settings);
    
    if('POST3' === NFSALESFORCECRM_MODE){
        // in NF3 - update status after refresh token for more detailed support
        $refresh_status = $refresh_token_object->get_comm_data();
        nfsalesforcecrm_update_comm_data_v2_9($refresh_status);
        
        wp_redirect(admin_url().'admin.php?page=nf-settings#'.NF_SalesforceCRM::BOOKMARK);
        exit;
    }else{
        wp_redirect('admin.php?page=nfsalesforcecrm-site-options&tab=access_credentials');
    }
}

/**
 * Adds characters removed by NF settings 
 * @param string $incoming_authcode
 * @return string Authorization code with trailing %3D added back in
 */
function nfsalesforcecrm_filter_authcode($incoming_authcode) {
    
    $wip_authcode = $incoming_authcode; // initial wip
    
    /*
     * Strip out URL if present - makes it easier for instructions
     */
    $wip_authcode = str_replace('https://login.salesforce.com/services/oauth2/success?code=','', $wip_authcode);
    
    /*
     * Add the stripped out characters when saving using Ninja_Forms class
     */
    if ('POST3' === NFSALESFORCECRM_MODE) {
        
        $wip_authcode = $wip_authcode . '%3D%3D';
    }
    
    $authcode = $wip_authcode;
    return $authcode;
}

/**
 * Iterates the array of objects and inserts them into Salesforce
 * @param type $object_request_list
 * @param type $request_object
 * @param type $api_parameter_array
 * @return boolean
 */
function nfsalesforcecrm_process_object_list_v2_9( $object_request_list, $request_object, $api_parameter_array){
    
    /*
     * Cycle through the object request list and add each new object to Salesforce
     * 
     */
    foreach ( $object_request_list as $salesforce_object ) {

        $object_field_array = $request_object->get_object_field_list( $salesforce_object );

        $new_record_parameter_array = $api_parameter_array;
        $new_record_parameter_array[ 'object_name' ] = $salesforce_object;
        $new_record_parameter_array[ 'field_array' ] = $object_field_array;

        $new_object_record = new SalesforcePostNewRecordObject_v2_9( $new_record_parameter_array );

        $new_record_id = $new_object_record->get_new_record_id();

        if ( $new_record_id ) {
            $request_object->link_child_objects( $salesforce_object, $new_record_id );
        }

        $temp_array = $new_object_record->get_comm_data();
        $new_object_array[ 'debug' ][] = $temp_array[ 'debug' ];
        $new_object_array[ 'status' ][] = $temp_array[ 'status' ]; // accumulate the statuses
    }


    /* add for duplicate field test */

    $duplicate_check_array = $request_object->get_duplicate_check_array();

    if ( !$duplicate_check_array ) {
        nfsalesforcecrm_update_comm_data_v2_9( $new_object_array );
        return false;
    }

    /*
     * Cycle through the duplicate check array and check if the given
     * Salesforce object and field have more than one of the same user value
     * If true, create a task with description identifying the object,
     * field, and value that is duplicated
     * 
     */
    foreach ( $duplicate_check_array as $salesforce_object => $field_check_array ) {
        
        $duplicate_check_parameter_array = $api_parameter_array;
        $duplicate_check_parameter_array[ 'object_name' ] = $salesforce_object;

        /*
         * NOTE: duplicate check is built as an array of arrays so that
         * multiple matches could be added in the future if needed;
         * currently checking only first array
         * 
         */
        $duplicate_check_parameter_array[ 'field_name' ] = $field_check_array[ 0 ][ 'salesforce_field' ];
        $duplicate_check_parameter_array[ 'field_value' ] = $field_check_array[ 0 ][ 'user_value' ];

        $duplicate_check_object = new SalesforceDuplicateCheck( $duplicate_check_parameter_array );

        $temp_array = $duplicate_check_object->get_comm_data();
        $new_object_array[ 'debug' ][] = $temp_array[ 'debug' ];
        $new_object_array[ 'status' ][] = $temp_array[ 'status' ]; // accumulate the statuses

        $response = $duplicate_check_object->get_duplicate_check_response();

        /*
         * If more than one entry is returned, there is a duplicate
         * Create a task 
         */
        
        if ( isset( $response[ 'totalSize' ] ) && 1 < $response[ 'totalSize' ] ) { 

            $task_to_review_duplicate_request_array = $api_parameter_array;
            $task_to_review_duplicate_request_array[ 'object_name' ] = 'Task';
            $task_to_review_duplicate_request_array[ 'field_array' ] = nfsalesforcecrm_build_duplicate_check_task_array_v2_9( $duplicate_check_parameter_array );

            $new_duplicate_task_record = new SalesforcePostNewRecordObject_v2_9( $task_to_review_duplicate_request_array );

            $temp_array = $new_duplicate_task_record->get_comm_data();
            $new_object_array[ 'debug' ][] = $temp_array[ 'debug' ];
            $new_object_array[ 'status' ][] = $temp_array[ 'status' ]; // accumulate the statuses
        }
        
    }

    nfsalesforcecrm_update_comm_data_v2_9( $new_object_array );
    
}



/*
 * Build the Task fields for reviewing the duplicate object, field, and value
 * found during the duplicate field check
 * 
 * Currently uses Task Subject and Description
 */
function nfsalesforcecrm_build_duplicate_check_task_array_v2_9( $parameter_array ) {

    /*
     * Build the Task Description based on the parameters that are duplicated
     * 
     */
    $description_intro = __( 'A recent form submission has a possible duplication in the following Object: ', 'ninja-forms-salesforce-crm' );
    
    $description_text = $description_intro 
            . $parameter_array[ 'object_name' ] . '.  '
            . __( 'Please check this field: ', 'ninja-forms-salesforce-crm' )
            . $parameter_array[ 'field_name' ] . ' '
            .  __( 'for a duplicate value: ', 'ninja-forms-salesforce-crm' )
            . $parameter_array[ 'field_value' ];

    $description_text = apply_filters( 'nfsalesforcecrm-duplicate-found-task-description', $description_text , $parameter_array);

    
    /*
     * Set the Task Due Date
     */
 
    $date = new DateTime(); // get a timestamp
    $date_format = apply_filters( 'nfsalesforcecrm_filter_date_interval_format', 'Y-m-d' ); // set the format for Salesforce
    $date_interval = apply_filters('nfsalesforcecrm_filter_duplicate_check_task_due_date','0'); // give developer option to set delay to task date
    date_add( $date, date_interval_create_from_date_string( $date_interval) ); // delay task by interval amount
    
    $formatted_date = $date->format( $date_format ); // format the date for Salesforce

    
    
    $field_array = array(
        'Subject' => apply_filters( 'nfsalesforcecrm-duplicate-found-task-subject', 'Duplicate found from web form submission' ),
        'Description' => $description_text,
        'ActivityDate'=>$formatted_date
    );

    return $field_array;
}




/**
 * Gets the contents of an href link
 * 
 * If unable to retrieve the contents of the link, returns FALSE
 * 
 * 
 * @param string $link Link sent in anchor href format
 * @return string $contents The contents of the link 
 */
function nfsalesforcecrm_extract_upload_contents_v2_9($link) {

    $contents = FALSE; // set default

    /*
     * Check for false or null link
     */
    if(!$link){
        
        return $contents;
    }
    
    $dom = new DOMDocument();

    /*
     * Attempt to parse link as html
     */
    libxml_use_internal_errors(true);
    $dom->loadHTML($link);
    $error_catch = libxml_get_last_error();
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    if ($error_catch) {

        return $contents;
    }

    /*
     * Attempt to extract first anchor tag
     */
    $anchors = $dom->getElementsByTagName('a');

    if (!isset($anchors[0])) {

        return $contents;
    } else {

        $href = $anchors[0]->getAttribute('href');
    }

    /*
     * Attempt to retrieve contents of anchor href
     */
    $get_contents = @file_get_contents($href);
    
    if(!$get_contents){

        return $contents;
    }
    
    /*
     * Successful getting contents
     */
    $contents = $get_contents;

    return $contents;
}
