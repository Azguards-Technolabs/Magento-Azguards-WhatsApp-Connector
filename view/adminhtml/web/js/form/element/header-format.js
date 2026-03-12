define([
    'Magento_Ui/js/form/element/select'
], function (Select) {
    'use strict';

    return Select.extend({
        initObservable: function () {
            this._super()
                .observe([
                    'textVisible',
                    'mediaVisible'
                ]);

            return this;
        },

        initialize: function () {
            this._super();
            this.updateVisibility(this.value());

            return this;
        },

        onUpdate: function (value) {
            this.updateVisibility(value);

            return this._super();
        },

        updateVisibility: function (value) {
            var normalizedValue = (value || '').toString().toUpperCase(),
                isText = normalizedValue === 'TEXT' || normalizedValue === '';

            this.textVisible(isText);
            this.mediaVisible(!isText);

            return this;
        }
    });
});
