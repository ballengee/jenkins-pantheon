<?php

/**
 * Adds filters specified by an Advanced Command
 *
 * Enables dashboard setting to apply hard coded filters
 *
 * @author stuartlb3
 */
class NF_SalesforceCRM_Admin_AdvancedCommands
{

    /**
     * Settings key for the Advanced Commands
     * @var string 
     */
    protected $settings_key;

    /**
     * Advanced command codes array
     *
     * @var array
     */
    protected $advanced_codes_array = array();

    /**
     * Adds the Advanced Commands setting
     * @param string $settings_key NF Settings key where advanced commands field is stored
     */
    public function __construct($settings_key)
    {
        $this->settings_key = $settings_key;

        $this->refreshSettings();
    }

    /**
     * Retrieves advanced command setting, generates advanced command array
     * 
     * Checks setting from DB and sanitizes, then explodes and trims spaces
     */
    public function refreshSettings()
    {
        $ninja_forms_settings = get_option('ninja_forms_settings');

        if (isset($ninja_forms_settings[ $this->settings_key ]) && is_string($ninja_forms_settings[ $this->settings_key ])) {

            $unsanitized = $ninja_forms_settings[ $this->settings_key ];
            
            $advanced_codes_setting = sanitize_text_field($unsanitized);

            $this->advanced_codes_array = array_map('trim', explode(',', $advanced_codes_setting));
        }
    }


    /**
     * Returns the stored advanced codes for the plugin
     *
     * @return array Advanced codes as array
     */
    public function advancedCommands()
    {
        return $this->advanced_codes_array;
    }

    /**
     * Given an advanced command, evaluates if it is set or not
     * @param string $command
     * @return bool TRUE is command is set, FALSE if not set
     */
    public function isAdvancedCommandSet($command = '')
    {
        if (in_array($command, $this->advanced_codes_array)) {

            $evaluation = TRUE;
        } else {

            $evaluation = FALSE;
        }

        return $evaluation;
    }

    /**
     * Searches advanced commands for variable values and returns variable
     *
     * Given a prefixed command, returns suffix, false on fail
     *
     * @param string $prefix
     * @return string|false Variable value from advanced command, false on unmatched
     */
    public function variableAdvancedCommand($prefix = '')
    {
        $return = FALSE;

        foreach ($this->advanced_codes_array as $advanced_command) {

            if (0 === strpos($advanced_command, $prefix)) {

                $return = str_replace($prefix, '', $advanced_command);

                break;
            }
        }

        return $return;
    }

    /**
     * Searches advanced commands for prefixed values and returns integer suffix
     *
     * Given a prefixed command, returns integer, false on fail
     *
     * @param string $prefix
     * @return integer|bool Description
     */
    public function intAdvancedCommmand($prefix = '')
    {
        $return = FALSE;

        $variable_advanced_command = $this->variableAdvancedCommand($prefix);

        if ($variable_advanced_command) {
            $return = intval($variable_advanced_command);
        }

        return $return;
    }

    /**
     * Searches advanced commands for prefixed values and returns array suffix
     *
     * Given a prefixed command, returns array, exploded on '-', false on fail
     *
     * ex:  samplecommand_A-B-C returns array (A,B,C)
     * 
     * @param string $prefix
     * @return array|bool Description
     */
    public function arrayAdvancedCommmand($prefix = '')
    {
        $return = FALSE;

        $variable_advanced_command = $this->variableAdvancedCommand($prefix);

        if ($variable_advanced_command) {

            $return = explode('-', $variable_advanced_command);
        }

        return $return;
    }

}
