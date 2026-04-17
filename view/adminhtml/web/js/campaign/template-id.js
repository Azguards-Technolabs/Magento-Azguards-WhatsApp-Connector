define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'jquery',
    'mage/url',
    'mage/translate'
], function (Select, registry, $, urlBuilder, $t) {
    'use strict';

    return Select.extend({
        defaults: {
            variablesContainer: '#campaign-variable-mapping-container',
            headerPreview: '#campaign-header-preview',
            mappingField: 'variable_mapping',
            mappingOptions: {}
        },

        /**
         * Invokes when the template is updated.
         */
        onUpdate: function (value) {
            console.log('WhatsApp Campaign JS: Template changed to:', value);
            this._super();

            // Clean up existing mapping and custom media
            this.source.set('data.' + this.mappingField, '');
            this.source.set('data.media_handle', '');
            this.source.set('data.media_url', '');

            this.fetchTemplateVariables(value);
        },

        /**
         * AJAX call to fetch variables and render inputs.
         */
        fetchTemplateVariables: function (templateId) {
            var self = this;
            // Use this.varsUrl injected from DataProvider (preferred)
            var url = this.varsUrl;
            var $container = $(this.variablesContainer);

            if (!url) {
                console.log('WhatsApp Campaign JS: varsUrl not in component config, checking global...');
                url = (window.whatsappCampaignConfig || {}).varsUrl;
            }

            if (!url) {
                console.error('WhatsApp Campaign JS: Template Variables URL not found in config!');
                // Final fallback (might fail due to lack of secret key)
                url = urlBuilder.build('whatsappconnect/campaign/variables');
            }

            if (!templateId) {
                $container.hide().empty();
                $(this.headerPreview).hide().empty();
                return;
            }

            // Start loader प्रॉपर्ली on body to avoid warnings
            $('body').trigger('processStart');

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                data: {
                    entity_id: templateId,
                    form_key: window.FORM_KEY,
                    isAjax: true
                }
            }).done(function (resp) {
                console.log('WhatsApp Campaign JS: Variables response:', resp);
                if (resp && resp.error) {
                    console.error('WhatsApp Campaign JS: Server error in response:', resp.message || resp.error);
                }
                self.renderVariables(resp.variables || []);
                self.renderHeaderPreview(resp.header_format, resp.header_image);
            }).fail(function (xhr, status, error) {
                console.error('WhatsApp Campaign JS: AJAX failed:', status, error);
                console.log('WhatsApp Campaign JS: RAW response:', xhr.responseText);
            }).always(function () {
                $('body').trigger('processStop');
            });
        },

        /**
         * Render dynamic inputs for variables.
         */
        renderVariables: function (variables) {
            var $container = $(this.variablesContainer);

            if (!$container.length) {
                console.error('WhatsApp Campaign JS: Variables container NOT FOUND in DOM!');
                return;
            }

            $container.empty();

            if (!variables || !variables.length) {
                $container.addClass('wa-hidden').hide();
                return;
            }

            $container.removeClass('wa-hidden').show();
            var $wrap = $('<div class="wa-variable-section"></div>');
            $wrap.append('<h4 class="wa-section-title">' + $t('Template Setup') + '</h4>');
            $wrap.append('<div class="wa-section-subtitle">' + $t('Map each template variable to an automatic customer field, or type a custom value.') + '</div>');

            var self = this;
            var mappingOptions = this.mappingOptions || {};
            variables.forEach(function (variable) {
                var name = typeof variable === 'object' ? variable.name : variable;
                var label = typeof variable === 'object' ? variable.label : variable;

                var $row = $('<div class="wa-variable-row"></div>');
                $row.append(
                    '<div class="wa-var-label">' +
                    '<div class="wa-var-title">' + label + '</div>' +
                    '<div class="wa-var-chip">{{' + name + '}}</div>' +
                    '</div>'
                );

                var existingMapping = {};
                try {
                    var mappingVal = self.source.get('data.' + self.mappingField);
                    if (mappingVal) {
                        existingMapping = typeof mappingVal === 'string' ? JSON.parse(mappingVal) : mappingVal;
                    }
                } catch (e) {
                    console.error('WhatsApp Campaign JS: Error parsing existing mapping', e);
                }

                var currentVal = existingMapping[name] || '';
                var currentSelect = '';
                var currentLiteral = '';
                if (currentVal && mappingOptions[currentVal]) {
                    currentSelect = currentVal;
                } else {
                    currentLiteral = currentVal;
                }

                var optionsHtml = '<option value="">' + $t('Auto') + '</option>';
                Object.keys(mappingOptions).forEach(function (key) {
                    var optLabel = mappingOptions[key];
                    var selectedAttr = (key === currentSelect) ? ' selected="selected"' : '';
                    optionsHtml += '<option value="' + key + '"' + selectedAttr + '>' + optLabel + '</option>';
                });

                $row.append(
                    '<div class="control">' +
                    '<div class="wa-control-wrap">' +
                    '<select class="admin__control-select wa-variable-select" data-varname="' + name + '" style="min-width: 220px;">' +
                    optionsHtml +
                    '</select>' +
                    '<div>' +
                    '<input type="text" class="wa-variable-literal" data-varname="' + name + '" value="' + (currentLiteral || '') + '" ' +
                    'placeholder="' + $t('Custom value (optional)') + '">' +
                    '<div class="wa-hint">' + $t('Tip: selecting a field clears custom value and vice‑versa.') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
                $wrap.append($row);
            });

            $container.append($wrap);

            // Bind update events
            $container.find('.wa-variable-select').on('change', function () {
                var $row = $(this).closest('.wa-variable-row');
                if ($(this).val()) {
                    $row.find('.wa-variable-literal').val('');
                }
                self.syncMapping();
            });
            $container.find('.wa-variable-literal').on('change keyup', function () {
                var $row = $(this).closest('.wa-variable-row');
                if ($(this).val()) {
                    $row.find('.wa-variable-select').val('');
                }
                self.syncMapping();
            });
        },

        /**
         * Sync inputs back to the hidden variable_mapping field.
         */
        syncMapping: function () {
            var mapping = {};
            var $container = $(this.variablesContainer);
            $container.find('.wa-variable-row').each(function () {
                var $row = $(this);
                var name = $row.find('[data-varname]').first().data('varname');
                if (name === undefined || name === null) {
                    return;
                }
                var literal = ($row.find('.wa-variable-literal').val() || '').toString();
                var selected = ($row.find('.wa-variable-select').val() || '').toString();

                if (literal.trim() !== '') {
                    mapping[name] = literal;
                } else if (selected.trim() !== '') {
                    mapping[name] = selected;
                } else {
                    mapping[name] = '';
                }
            });
            this.source.set('data.' + this.mappingField, JSON.stringify(mapping));
        },

        /**
         * Render media preview for header with dynamic upload option.
         */
        renderHeaderPreview: function (format, image) {
            var $preview = $(this.headerPreview);
            if (!$preview.length) return;

            $preview.empty();

            if (!format || format.toUpperCase() === 'TEXT') {
                $preview.addClass('wa-hidden').hide();
                return;
            }

            $preview.removeClass('wa-hidden').show();

            var currentMediaUrl = this.source.get('data.media_url') || image;
            var currentMediaHandle = this.source.get('data.media_handle');
            var getPreviewUrl = function (url, handle) {
                if (!url) return url;
                if (!handle) return url;
                return url + (url.indexOf('?') === -1 ? '?' : '&') + 'cb=' + encodeURIComponent(handle);
            };
            var $card = $('<div class="wa-media-card"></div>');
            $card.append(
                '<div class="wa-media-card__top">' +
                '<div class="wa-media-card__title">' + $t('Header Preview') + '</div>' +
                '<div class="wa-media-card__badge">' + $t('Format') + ': ' + format + '</div>' +
                '</div>'
            );

            var $body = $('<div class="wa-media-card__body"></div>');
            var $thumb = $('<div class="wa-media-thumb"></div>');
            if (currentMediaUrl && format.toUpperCase() === 'IMAGE') {
                $thumb.append('<img src="' + getPreviewUrl(currentMediaUrl, currentMediaHandle) + '" alt="Header image">');
            } else {
                $thumb.append('<div class="wa-upload-status">' + $t('No preview available') + '</div>');
            }

            var $actions = $('<div class="wa-media-actions"></div>');
            $actions.append('<div class="wa-hint">' + $t('Upload a custom media override for this campaign. This does not change the template itself.') + '</div>');

            var $uploaderRow = $('<div class="wa-media-actions__row"></div>');
            var $fileInput = $('<input type="file" class="wa-media-file-input" style="display:none;">');
            var $uploadBtn = $('<button type="button" class="action-secondary"><span>' + $t('Upload New ' + format) + '</span></button>');
            var $status = $('<span class="wa-upload-status upload-status"></span>');
            $uploaderRow.append($fileInput).append($uploadBtn).append($status);
            $actions.append($uploaderRow);

            $body.append($thumb).append($actions);
            $card.append($body);
            $preview.append($card);

            var self = this;
            $uploadBtn.on('click', function () { $fileInput.click(); });

            $fileInput.on('change', function () {
                var file = this.files[0];
                if (!file) return;

                $status.text($t('Uploading...')).css('color', '#ed672d');
                $uploadBtn.prop('disabled', true);

                self.handleMediaUpload(file, format, function (resp) {
                    $uploadBtn.prop('disabled', false);
                    if (resp.success) {
                        $status.text($t('Upload Successful!')).css('color', '#27ae60');
                        self.source.set('data.media_handle', resp.media_handle);
                        self.source.set('data.media_url', resp.url);

                        // Refresh preview
                        if (format.toUpperCase() === 'IMAGE') {
                            var previewUrl = getPreviewUrl(resp.url, resp.media_handle || (Date.now() + ''));
                            var $img = $thumb.find('img');
                            if (!$img.length) {
                                $thumb.empty();
                                $thumb.append('<img src="' + previewUrl + '" alt="Header image">');
                            } else {
                                $img.attr('src', previewUrl);
                            }
                        }
                    } else {
                        $status.text($t('Upload Failed: ') + (resp.error || 'Unknown error')).css('color', '#e74c3c');
                    }
                });
            });
        },

        /**
         * Handle Media Upload via AJAX
         */
        handleMediaUpload: function (file, format, callback) {
            var uploadUrl = (window.whatsappCampaignConfig || {}).uploadUrl;
            if (!uploadUrl) {
                console.error('WhatsApp Campaign JS: Upload URL not found!');
                return callback({ success: false, error: 'Upload configuration missing' });
            }

            var formData = new FormData();
            formData.append('media_upload', file);
            formData.append('form_key', window.FORM_KEY);
            formData.append('param_name', 'media_upload');

            $.ajax({
                url: uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (resp) {
                callback(resp);
            }).fail(function (xhr, status, error) {
                console.error('WhatsApp Campaign JS: Media upload failed', status, error);
                callback({ success: false, error: error });
            });
        },

        /**
         * Initial load.
         */
        initialize: function () {
            this._super();
            var self = this;

            // Senior Level: Wait for both registry and source data to be ready
            registry.async(this.name)(function (component) {
                var initialTemplateId = component.value() || self.source.get('data.template_entity_id');
                console.log('WhatsApp Campaign JS: Initial Template ID found:', initialTemplateId);

                if (initialTemplateId) {
                    // Slight delay to ensure DOM and other components are ready
                    setTimeout(function () {
                        component.fetchTemplateVariables(initialTemplateId);
                    }, 100);
                }
            });
            return this;
        }
    });
});
