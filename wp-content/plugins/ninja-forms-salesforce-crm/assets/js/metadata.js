/**
 * Makes sure that metadata keys are not duplicates.
 *
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1
 */
var nfSalesforceMetadataController = Marionette.Object.extend({
    initialize: function () {

        var nfRadio = Backbone.Radio;

        this.listenTo(nfRadio.channel('app'), 'replace:fieldKey', this.replaceFieldKey);
    },

    /**
     * Listen for field key changes and update our
     * option repeater values as necessary.
     * 
     * @since 3.2
     * @param backbone.model  dataModel     the action model making the call
     * @param backbone.model  keyModel      the field model that was updated
     * @param backbone.model  settingModel  the setting model being passed
     * @return void
     */
    replaceFieldKey: function (dataModel, keyModel, settingModel) {
        // Referenced our Radio.
        var nfRadio = Backbone.Radio;

        // check for the action type
        if ('addtosalesforce' !== dataModel.get('type'))
            return false;

        var oldKey = nfRadio.channel('app').request('get:fieldKeyFormat', keyModel._previousAttributes[ 'key' ]);
        var newKey = nfRadio.channel('app').request('get:fieldKeyFormat', keyModel.get('key'));

        // If the setting has something in it...
        if ('undefined' != typeof dataModel.get('salesforce_field_map')) {

            //salesforce_field_map
            var metaModel = dataModel.get('salesforce_field_map');

            if (Array.isArray(metaModel)) {

                metaModel.forEach(function (model) {

                    if ('undefined' !== typeof model.form_field) {

                        var tempFormField = model.form_field;

                        model.form_field = tempFormField.replace(oldKey, newKey);
                    }
                });
            } // Otherwise we don't know what it is, so end gracefully

            dataModel.set('salesforce_field_map', metaModel);
        }
    }
});

jQuery(document).ready(function ($) {

    new nfSalesforceMetadataController();
});