define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'jquery'
], function (Select, registry, $) {
    'use strict';

    return Select.extend({
        defaults: {
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            },
            contactsWrapper: '#campaign_custom_contacts_wrapper'
        },

        /**
         * Initialize visibility and config.
         */
        initialize: function () {
            this._super();
            var self = this;

            // Share searchUrl with global config for Select2 in PHTML (Senior Level fallback)
            if (this.searchUrl) {
                window.whatsappCampaignConfig = window.whatsappCampaignConfig || {};
                window.whatsappCampaignConfig.searchUrl = this.searchUrl;
            }

            // Ensure we are initially hidden to avoid "both showing" on load
            $(this.contactsWrapper).addClass('wa-hidden').hide();

            // Use registry.async to ensure target fields are ready before toggling
            registry.async(this.parentName + '.customer_group_ids')(function (component) {
                var initialValue = self.value() || self.source.get('data.target_type');
                console.log('WhatsApp Campaign JS: Initial target_type found:', initialValue);

                // Delay to ensure the HTML wrapper is rendered in the DOM
                setTimeout(function () {
                    self.toggleCustomUi(initialValue);
                }, 150);
            });

            return this;
        },

        /**
         * Transition visibility for the updated value.
         */
        onUpdate: function (value) {
            this._super();
            this.toggleCustomUi(value);
        },

        /**
         * Handle custom UI visibility and internal Magento field toggling.
         */
        toggleCustomUi: function (value) {
            var isContacts = (value === 'contacts');
            var $wrapper = $(this.contactsWrapper);

            // Toggle the custom HTML wrapper (for Select2)
            if (isContacts) {
                $wrapper.removeClass('wa-hidden').show();
            } else {
                $wrapper.addClass('wa-hidden').hide();
            }

            // Toggle standard Magento fields via registry
            registry.get(this.parentName + '.customer_group_ids', function (component) {
                component.visible(!isContacts);
            });

            registry.get(this.parentName + '.customer_ids', function (component) {
                component.visible(isContacts);
            });

            console.log('WhatsApp Campaign JS: Custom UI visibility set to:', isContacts);
        }
    });
});
