/**
 * Button Row component for WhatsApp Template form.
 *
 * Watches the "type" select field inside each dynamicRows record and
 * toggles field visibility depending on the selected button type:
 *
 *  - COPY_CODE  → show "coupon_code", hide "text" & "value"
 *  - all others → show "text" & "value", hide "coupon_code"
 */
define([
    'Magento_Ui/js/dynamic-rows/record',
    'ko',
    'underscore'
], function (Record, ko, _) {
    'use strict';

    return Record.extend({

        defaults: {
            /** Names (relative to this record) of the fields we manage */
            typeFieldName: 'type',
            textFieldName: 'text',
            valueFieldName: 'value',
            couponFieldName: 'coupon_code',

            /** Track active type so children can observe */
            activeButtonType: ''
        },

        /**
         * After all children have been initialised, set up the type observer.
         */
        initialize: function () {
            this._super();
            this._observeType();
            return this;
        },

        /**
         * Find the `type` child element and watch its value.
         * Uses a short polling loop because children may not be ready yet.
         */
        _observeType: function () {
            var self = this,
                delay = 200,
                attempts = 0,
                max = 30;

            var poll = setInterval(function () {
                var typeEl = self._getChild(self.typeFieldName);
                if (typeEl) {
                    clearInterval(poll);
                    // Set initial state immediately
                    self._applyVisibility(typeEl.value());
                    // React to future changes
                    typeEl.value.subscribe(function (newType) {
                        self._applyVisibility(newType);
                    });
                } else if (++attempts >= max) {
                    clearInterval(poll);
                }
            }, delay);
        },

        /**
         * Show/hide fields based on button type.
         *
         * @param {string} selectedType
         */
        _applyVisibility: function (selectedType) {
            var isCopyCode = selectedType === 'COPY_CODE';

            // Show text field for all types now (including COPY_CODE for its label)
            this._setVisible(this.textFieldName, true);
            this._setVisible(this.valueFieldName, !isCopyCode);
            this._setVisible(this.couponFieldName, isCopyCode);

            // Remove required validation from text field when COPY_CODE is active
            var textEl = this._getChild(this.textFieldName);
            if (textEl) {
                if (isCopyCode) {
                    textEl.required(false);
                    textEl.validation = _.omit(textEl.validation || {}, 'required-entry');
                } else {
                    textEl.required(true);
                }
            }
        },

        /**
         * Set visibility of a child field by relative name.
         *
         * @param {string} fieldName  relative child name
         * @param {boolean} visible
         */
        _setVisible: function (fieldName, visible) {
            var el = this._getChild(fieldName);
            if (el) {
                el.visible(visible);
            }
        },

        /**
         * Resolve a child element by its relative name.
         *
         * @param {string} name
         * @returns {object|undefined}
         */
        _getChild: function (name) {
            // elems() contains all direct children collected by Magento UI
            return _.find(this.elems(), function (el) {
                // el.index is the short name (without the full path prefix)
                return el.index === name;
            });
        }
    });
});
