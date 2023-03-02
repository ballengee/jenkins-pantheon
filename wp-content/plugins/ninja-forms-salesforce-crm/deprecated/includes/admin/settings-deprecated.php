<?php
/* ----------
  CREATE OPTIONS PAGE AND GET SETTINGS FOR SITE-WIDE VALUES
  ------------------------------------------------------------------------------------------------------------ */


add_action( 'admin_menu', 'nfsalesforcecrm_create_options_page_location', 100 );
/*
 * Create options page location under Ninja Forms menu
 * 
 */

function nfsalesforcecrm_create_options_page_location() {

    add_submenu_page(
            'ninja-forms', //parent slug
            __( 'Salesforce CRM Settings', 'ninja-forms-salesforce-crm' ), //page title
            __( 'Salesforce CRM Settings', 'ninja-forms-salesforce-crm' ), //menu title
            'manage_options', //capability
            'nfsalesforcecrm-site-options', //menu-slug
            'nfsalesforcecrm_site_options_display_page' //display function
    );
}

/**
 * Output the html for the options page 
 * @global array $nfsalesforcecrm_settings
 * 
 * tabbed settings from Tom McFarlin 
 * http://code.tutsplus.com/tutorials/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page--wp-24971
 * 
 */
function nfsalesforcecrm_site_options_display_page() {
    ?>
    <div class="wrap">
        <?php screen_icon( 'options-general' ); ?>
        <h2><?php _e( 'Ninja Forms Salesforce CRM Settings', 'ninja-forms-salesforce-crm' ); ?></h2>

        <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'access_credentials'; ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=nfsalesforcecrm-site-options&tab=access_credentials" class="nav-tab <?php echo $active_tab == 'access_credentials' ? 'nav-tab-active' : ''; ?>">Salesforce Credentials</a>
            <a href="?page=nfsalesforcecrm-site-options&tab=object_fields" class="nav-tab <?php echo $active_tab == 'object_fields' ? 'nav-tab-active' : ''; ?>">Available Objects and Fields</a>
            <a href="?page=nfsalesforcecrm-site-options&tab=comm_details" class="nav-tab <?php echo $active_tab == 'comm_details' ? 'nav-tab-active' : ''; ?>">Communication Details</a>
        </h2>



        <?php
        if ( $active_tab == 'access_credentials' ) {
            ?> <form method="post" action="options.php" class="nfsalesforcecrm_site_options_form"><?php
            settings_fields( 'nfsalesforcecrm-site-options' );
            do_settings_sections( 'nfsalesforcecrm-site-options-section' );
            ?> <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'ninja-forms-salesforce-crm' ); ?>" />
                </p>

            </form>
            <form method="post" action="" >  
                <input type="submit" value="Click to generate new refresh token from authorization code" />
                <input type="hidden" name="action" value="nfsalesforcecrm_generate_refresh_token_listener" />

            </form><?php
        } else if ( $active_tab == 'object_fields' ) {

            echo nfsalesforcrcrm_output_account_data();
        } else if ( $active_tab == 'comm_details' ) {

            echo nfsalesforcecrm_output_comm_status();
        }
        ?>


        <?php do_action( 'nfsalesforcecrm_oath_helpers' ); ?>
    </div><!--end .wrap-->
    <?php
}

add_action( 'admin_init', 'nfsalesforcecrm_create_sitewide_settings' );

/**
 * Create options field and section
 * 
 */
function nfsalesforcecrm_create_sitewide_settings() {

    /* --- register setting --- */
    $settings_fields = 'nfsalesforcecrm-site-options';
    $nfsalesforcecrm_settings = 'nfsalesforcecrm_settings';

    register_setting( $settings_fields, $nfsalesforcecrm_settings, 'nfsalesforcecrm_validate_settings' ); //whitelists our setting to allow it to appear in a given form	


    /* --- Add Settings Section --- */

    $section_id = 'nfsalesforcecrm_site_section'; //id for our section
    $section_title = __( 'Ninja Forms Salesforce CRM Settings', 'ninja-forms-salesforce-crm' ); // about our section
    $section_output_function = 'nfsalesforcecrm_section_output'; //

    $do_settings_section = 'nfsalesforcecrm-site-options-section'; //on which page should our new section go

    add_settings_section(
            $section_id
            , $section_title
            , $section_output_function
            , $do_settings_section
    );


    /* --- Create array to add each sitewide option --- */

    $options_array = array(
        'consumer_key' => array(
            'field_id' => '0',
            'field_title' => __( 'Salesforce Consumer Key', 'ninja-forms-salesforce-crm' ),
            'field_output_function' => 'nfsalesforcecrm_consumer_key_field_output'
        ),
        'consumer_secret' => array(
            'field_id' => 'nfsalesforcecrm_consumer_secret',
            'field_title' => __( 'Salesforce Consumer Secret', 'ninja-forms-salesforce-crm' ),
            'field_output_function' => 'nfsalesforcecrm_consumer_secret_field_output'
        ),
        'authorization_code' => array(
            'field_id' => 'nfsalesforcecrm_authorization_code',
            'field_title' => __( 'Salesforce Authorization Code', 'ninja-forms-salesforce-crm' ),
            'field_output_function' => 'nfsalesforcecrm_authorization_code_field_output'
        ),
        'refresh_token' => array(
            'field_id' => 'nfsalesforcecrm_refresh_token',
            'field_title' => __( 'Salesforce Refresh Token', 'ninja-forms-salesforce-crm' ),
            'field_output_function' => 'nfsalesforcecrm_refresh_token_field_output'
        ),
        'refresh_salesforce_objects' => array(
            'field_id' => 'nfsalesforcecrm_refresh_salesforce_objects',
            'field_title' => __( 'Refresh your Salesforce Object Data?', 'ninja-forms-salesforce-crm' ),
            'field_output_function' => 'nfsalesforcecrm_refresh_salesforce_objects_field_output'
        ),
        'available_objects' => array(
            'field_id' => 'nfsalesforcecrm_available_objects',
            'field_title' => __( 'Objects Available in Ninja Forms', 'ninja-forms-salesforce-crm' ),
            'field_output_function' => 'nfsalesforcecrm_available_objects_field_output'
        ),
    );


    /* --- Loop through each option array to add a field to a do_settings_section --- */

    foreach ( $options_array as $option ) {

        add_settings_field(
                $option[ 'field_id' ]//unique id for field
                , $option[ 'field_title' ]//field title
                , $option[ 'field_output_function' ]//function callback
                , $do_settings_section //on which page should our new field go
                , $section_id //in which settings section should our new field go
        );
    }
}

/*
 * Output the settings section tagline 
 *
 */

function nfsalesforcecrm_section_output() {

    echo __( "Please complete the necessary settings for your Salesforce account", 'ninja-forms-salesforce-crm' );
}

/*
  Create the form-specific options
  ----- */


add_action( 'admin_init', 'nfsalesforcecrm_admin_hook', 12 );

/**
 * Hook into the flow
 * 
 */
function nfsalesforcecrm_admin_hook() {

    nfsalesforcecrm_create_form_options();
}

/**
 * Create the form-specific options
 * 
 */
function nfsalesforcecrm_create_form_options() {

    $metabox_settings_array = array( //Add form data as person?
        array(
            'name' => 'nfsalesforcecrm_send_to_salesforce',
            'type' => 'checkbox',
            'label' => __( 'Send this form to Salesforce?', 'ninja-forms-salesforce-crm' ),
            'desc' => __( 'Do you want this form data sent to Salesforce?', 'ninja-forms-salesforce-crm' ),
            'default_value' => false,
        ),
    );


    $args = array(
        'page' => 'ninja-forms',
        'tab' => 'form_settings',
        'slug' => 'nfsalesforcecrm_form_settings',
        'title' => __( 'Salesforce CRM Settings', 'ninja-forms-salesforce-crm' ),
        /** @param array of metabox settings arrays */
        'settings' => apply_filters( 'filter_nfsalesforce_form_options', $metabox_settings_array ),
    );

    if ( function_exists( 'ninja_forms_register_tab_metabox_options' ) ) {

        ninja_forms_register_tab_metabox( $args );
    }
}

/**
 * Output the 'display raw communication option' checkbox
 * 
 * @global array $nfsalesforcecrm_settings
 * 
 */
function nfsalesforcecrm_refresh_salesforce_objects_field_output() {

    global $nfsalesforcecrm_settings;


    if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_salesforce_objects' ] ) ) {

        $checked = $nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_salesforce_objects' ];
    } else {
        $checked = 'no';
    }


    ob_start();
    ?>
    <label for="nfsalesforcecrm_refresh_salesforce_objects-yes">
        <input 
            id="nfsalesforcecrm_refresh_salesforce_objects-yes"
            name="nfsalesforcecrm_settings[nfsalesforcecrm_refresh_salesforce_objects]"
            type="radio" 
            value = "TRUE" 
            <?php checked( $checked, 'TRUE', true ); ?>
            />
        Yes</label> <br />
    <label for="nfsalesforcecrm_refresh_salesforce_objects-no">
        <input 
            id="nfsalesforcecrm_display_raw_comm-no"
            name="nfsalesforcecrm_settings[nfsalesforcecrm_refresh_salesforce_objects]"
            type="radio" 
            value = "FALSE" 
            <?php checked( $checked, 'FALSE', true ); ?>
            />
        No</label><br />


    <?php
    echo ob_get_clean();
}

/**
 * Validate the values before saving to database
 * 
 * @param type $input
 * @return type
 * 
 */
function nfsalesforcecrm_validate_settings( $input ) {

    $output = array();

    foreach ( $input as $key => $value ) {

        if ( isset( $input[ $key ] ) ) {

            $output[ $key ] = strip_tags( stripslashes( $input[ $key ] ) );
        }// endif
    } //end foreach

    return apply_filters( 'nfsalesforcecrm_validate_settings', $output, $input );
}

/**
 * Create HTML for communication status as string for display
 * 
 * 
 * @return string
 * @see nfsalesforcecrm_site_settings_display_page
 *
 */
function nfsalesforcecrm_output_comm_status() {

    global $nfsalesforcecrm_settings;
    global $nfsalesforcecrm_comm_data;


    if ( isset( $nfsalesforcecrm_comm_data[ 'status' ] ) ) {
        $salesforce_comm_status = '';
        if ( is_array( $nfsalesforcecrm_comm_data[ 'status' ] ) ) {
            foreach ( $nfsalesforcecrm_comm_data[ 'status' ] as $line_item ) {

                $salesforce_comm_status .= $line_item . '<br />';
            }
        } else {
            $salesforce_comm_status = $nfsalesforcecrm_comm_data[ 'status' ];
        }
    } else {
        $salesforce_comm_status = apply_filters( 'nfsalesforcecrm_modify_comm_status_default', "No communication has been detected.  Please test using your created form." );
    }


    ob_start();
    ?>

    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row"><?php _e( 'Communication Status', 'ninja-forms-salesforce-crm' ); ?></th>
                <td>

                    <?php echo $salesforce_comm_status; ?>

                </td>
            </tr>

            <?php
            if ( isset( $nfsalesforcecrm_comm_data[ 'debug' ] ) && is_array( $nfsalesforcecrm_comm_data[ 'debug' ] ) ) {

                foreach ( $nfsalesforcecrm_comm_data[ 'debug' ] as $array ) {
                    if ( !is_array( $array ) ) {
                        continue;
                    }
                    foreach ( $array as $comm_data_array ) {
                        ?>		
                        <tr valign="top">
                            <th scope="row"><?php echo $comm_data_array[ 'heading' ] ?></th>
                            <td>
                                <?php echo $comm_data_array[ 'value' ]; ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
            }
            ?>

        </tbody>
    </table>

    <?php
    $comm_status_string = ob_get_clean();

    return $comm_status_string;
}

function nfsalesforcecrm_open_auth_help() {

    global $nfsalesforcecrm_settings;
    ?>

    <h2><?php _e( 'Get Salesforce AuthCode', 'ninja-forms-salesforce-crm' ); ?></h2>


    <p><?php _e( 'Step 1. Open a new browser tab and log into your Salesforce Account.', 'ninja-forms-salesforce-crm' ); ?></p>
    <p><?php _e( 'Step 2. Generate your authorization code by clicking the link below.', 'ninja-forms-salesforce-crm' ); ?></p>

    <p><?php _e( 'Step 3. A new window will open.  Copy everything from the new URL after the phrase "?code=" and paste it into "Salesforce Authorization Code" field on the Salesforce Credentials tab.', 'ninja-forms-salesforce-crm' ); ?></p>

    <?php
}


