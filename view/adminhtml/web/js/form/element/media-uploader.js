define([
    'Magento_Ui/js/form/element/file-uploader',
    'uiRegistry',
    'mage/url',
    'jquery'
], function (FileUploader, registry, url, $) {
    'use strict';

    return FileUploader.extend({
        defaults: {
            documentIdField: '',
            previewLinkField: '',
            resolveUrl: 'whatsappconnect/template/resolveMedia'
        },

        initialize: function () {
            this._super();
            this.checkForMissingPreview();

            return this;
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

        /**
         * Checks if we have a handler but no preview link, and attempts to resolve it.
         */
        checkForMissingPreview: function () {
            var self = this,
                handler = this.getFieldValue(this.documentIdField),
                preview = this.getFieldValue(this.previewLinkField);

            if (handler && !preview) {
                this.resolveMedia(handler);
            }
        },

        /**
         * Resolves a media handler via the backend.
         */
        resolveMedia: function (handler) {
            var self = this;

            $.ajax({
                url: url.build(this.resolveUrl),
                data: { handler: handler },
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success && response.preview_url) {
                        self.setFieldValue(self.previewLinkField, response.preview_url);
                        // Update uploader preview if it's empty
                        if (!self.value().length) {
                            var resolvedFile = {
                                name: response.document_id || 'Media',
                                url: response.preview_url,
                                type: 'image/png' // Generic
                            };
                            self.value([resolvedFile]);
                        }
                    }
                }
            });
        },

        getFieldValue: function (target) {
            if (!target) return null;
            var field = registry.get(target);
            return field ? field.value() : null;
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
