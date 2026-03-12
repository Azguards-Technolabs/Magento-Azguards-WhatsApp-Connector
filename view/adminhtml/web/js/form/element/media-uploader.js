define([
    'Magento_Ui/js/form/element/file-uploader',
    'uiRegistry'
], function (FileUploader, registry) {
    'use strict';

    return FileUploader.extend({
        defaults: {
            documentIdField: '',
            previewLinkField: ''
        },

        onFileUploaded: function (event, data) {
            var file = data.result || {};

            this._super(event, data);
            this.applyMediaData(file);
        },

        removeFile: function (file) {
            this._super(file);
            this.applyMediaData({});

            return this;
        },

        applyMediaData: function (file) {
            this.setFieldValue(this.documentIdField, file.document_id || '');
            this.setFieldValue(this.previewLinkField, file.preview_link || '');

            return this;
        },

        setFieldValue: function (target, value) {
            if (!target) {
                return;
            }

            registry.get(target, function (field) {
                if (field && typeof field.value === 'function') {
                    field.value(value);
                }
            });
        }
    });
});
