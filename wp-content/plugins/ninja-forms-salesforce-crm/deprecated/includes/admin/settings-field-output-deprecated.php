<?php

/**
 * Output the consumer_key form html
 *  
 */
function nfsalesforcecrm_consumer_key_field_output() {

    global $nfsalesforcecrm_settings;

    ob_start();
    ?>

    <input 
        id="nfsalesforcecrm_consumer_key"
        name="nfsalesforcecrm_settings[nfsalesforcecrm_consumer_key]"
        size = "50"
        type="text" 
        value = "<?php
        if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ] ) ) {
            echo $nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ];
        }
        ?>" 
        />

    <?php
    echo ob_get_clean();
}

/**
 * Output the consumer_secret form html
 *  
 */
function nfsalesforcecrm_consumer_secret_field_output() {

    global $nfsalesforcecrm_settings;

    ob_start();
    ?>

    <input 
        id="nfsalesforcecrm_consumer_secret"
        name="nfsalesforcecrm_settings[nfsalesforcecrm_consumer_secret]"
        size = "50"
        type="text" 
        value = "<?php
        if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ] ) ) {
            echo $nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_secret' ];
        }
        ?>" 
        />

    <?php
    echo ob_get_clean();
}

/**
 * Output the authorization_code form html
 *  
 */
function nfsalesforcecrm_authorization_code_field_output() {

    global $nfsalesforcecrm_settings;

    $nfsalesforcecrm_connection = apply_filters('nfsalesforcecrm_set_connection_type','login');
    
    $url = 'https://'.$nfsalesforcecrm_connection.'.salesforce.com/services/oauth2/authorize?response_type=code&client_id=';

    $url .= $nfsalesforcecrm_settings[ 'nfsalesforcecrm_consumer_key' ];
    $url.='&redirect_uri=https://'.$nfsalesforcecrm_connection.'.salesforce.com/services/oauth2/success';

    ob_start();
    ?>

    <input 
        id="nfsalesforcecrm_authorization_code"
        name="nfsalesforcecrm_settings[nfsalesforcecrm_authorization_code]"
        size = "75"
        type="text" 
        value = "<?php
        if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_authorization_code' ] ) ) {
            echo $nfsalesforcecrm_settings[ 'nfsalesforcecrm_authorization_code' ];
        }
        ?>" 
        />
    <span><a href="<?php echo $url; ?>" target="_blank">Click to generate open authorization code</a></span>
    <?php
    echo ob_get_clean();
}

/**
 * Output the refresh_token form html
 *  
 */
function nfsalesforcecrm_refresh_token_field_output() {

    global $nfsalesforcecrm_settings;

    ob_start();
    ?>

    <input 
        id="nfsalesforcecrm_refresh_token"
        name="nfsalesforcecrm_settings[nfsalesforcecrm_refresh_token]"
        size = "100"
        type="text" 
        readonly
        value = "<?php
        if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ] ) ) {
            echo $nfsalesforcecrm_settings[ 'nfsalesforcecrm_refresh_token' ];
        }
        ?>" 
        />

    <?php
    echo ob_get_clean();
}

/**
 * Output the security token form html
 * 
 * This does not appear to be used 
 *  
 */
function nfsalesforcecrm_instance_url_field_output() {

    global $nfsalesforcecrm_settings;

    ob_start();
    ?>

    <input 
        id="nfsalesforcecrm_intance_url"
        name="nfsalesforcecrm_settings[nfsalesforcecrm_instance_url]"
        size = "50"
        type="text" 
        value = "<?php
        if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_instance_url' ] ) ) {
            echo $nfsalesforcecrm_settings[ 'nfsalesforcecrm_instance_url' ];
        }
        ?>" 
        />

    <?php
    echo ob_get_clean();
}

/**
 * Output the available_objects form html
 *  
 */
function nfsalesforcecrm_available_objects_field_output() {

    global $nfsalesforcecrm_settings;

    ob_start();
    ?>

    <input 
        id="nfsalesforcecrm_available_objects"
        name="nfsalesforcecrm_settings[nfsalesforcecrm_available_objects]"
        size = "100"
        type="text" 
        value = "<?php
    if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_available_objects' ] ) && 0 < strlen( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_available_objects' ] ) ) {
        echo $nfsalesforcecrm_settings[ 'nfsalesforcecrm_available_objects' ];
    } else {

        echo 'Lead';
    }
    ?>" 
        />
    <p><?php _e( 'Enter the objects from Salesforce you wish to use in your Ninja Forms, separated by a comma.', 'ninja-forms-salesforce-crm' ); ?></p>
    <p><?php _e( 'Here are some typical use cases.  Copy and paste any of these for a quick start:', 'ninja-forms-salesforce-crm' ); ?></p>
    <p><?php echo 'Lead'; ?></p>
    <p><?php echo 'Lead, Task'; ?></p>
    <p><?php echo 'Contact, Account'; ?></p>
    <p><?php echo 'Contact, Account, Opportunity'; ?></p>
    <hr />
    <?php
    echo ob_get_clean();
}

/**
 * Output the 'display raw communication option' checkbox
 * 
 * @global array $nfsalesforcecrm_settings
 * 
 */
function nfsalesforcecrm_display_raw_comm_field_output() {

    global $nfsalesforcecrm_settings;


    if ( isset( $nfsalesforcecrm_settings[ 'nfsalesforcecrm_display_raw_comm' ] ) ) {

        $checked = $nfsalesforcecrm_settings[ 'nfsalesforcecrm_display_raw_comm' ];
    } else {
        $checked = 'no';
    }


    ob_start();
    ?>
    <label for="nfsalesforcecrm_display_raw_comm-yes">
        <input 
            id="nfsalesforcecrm_display_raw_comm-yes"
            name="nfsalesforcecrm_settings[nfsalesforcecrm_display_raw_comm]"
            type="radio" 
            value = "TRUE" 
    <?php checked( $checked, 'TRUE', true ); ?>
            />
        Yes</label> <br />
    <label for="nfsalesforcecrm_display_raw_comm-no">
        <input 
            id="nfsalesforcecrm_display_raw_comm-no"
            name="nfsalesforcecrm_settings[nfsalesforcecrm_display_raw_comm]"
            type="radio" 
            value = "FALSE" 
    <?php checked( $checked, 'FALSE', true ); ?>
            />
        No</label><br />


    <?php
    echo ob_get_clean();
}
