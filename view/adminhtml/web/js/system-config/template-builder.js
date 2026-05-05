define([
    'jquery'
], function ($) {
    'use strict';

    function extractValue(data, path) {
        var segments = path.split('.');
        var value = data;

        for (var i = 0; i < segments.length; i++) {
            if (value === null || typeof value !== 'object' || typeof value[segments[i]] === 'undefined') {
                return '';
            }
            value = value[segments[i]];
        }

        return value;
    }

    function replaceVariables(template, data) {
        return (template || '').replace(/\{\{\s*([a-zA-Z0-9_.#\/]+)\s*\}\}/g, function (match, key) {
            if (key.indexOf('#items') !== -1 || key.indexOf('/items') !== -1) {
                return match;
            }

            var value = extractValue(data, key);
            return (value !== undefined && value !== null) ? String(value) : match;
        });
    }

    function resolveTemplate(template, data) {
        var rendered = template || '';

        // Handle items loop
        rendered = rendered.replace(/\{\{\#items\}\}([\s\S]*?)\{\{\/items\}\}/g, function (match, rowTemplate) {
            var rows = [];
            var items = data.items || [];

            $.each(items, function (index, item) {
                rows.push(replaceVariables(rowTemplate, item));
            });

            return rows.join('\n');
        });

        return $.trim(replaceVariables(rendered, data));
    }

    function parseJson(value, fallback) {
        try {
            return JSON.parse(value);
        } catch (e) {
            return fallback;
        }
    }

    /**
     * Requirement 7: Convert variables to indexed placeholders and maintain mapping.
     * Returns { text: transformedText, examples: [val1, val2] }
     */
    function processTemplateVariables(text, sampleData) {
        if (!text) return { text: '', examples: [] };
        var counter = 1;
        var map = {};
        var examples = [];

        var transformedText = text.replace(/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/g, function (match, key) {
            if (!map[key]) {
                map[key] = counter++;
                var val = extractValue(sampleData, key);
                examples.push(val || key);
            }
            return '{{' + map[key] + '}}';
        });

        return { text: transformedText, examples: examples };
    }

    return function (config) {
        var selectors = config.selectors || {};
        var sampleData = config.sampleData || {};

        // Real fields (hidden)
        var $realTemplateName = $(selectors.templateName);
        var $realCategory = $(selectors.category);
        var $realLanguage = $(selectors.language);
        var $realHeaderType = $(selectors.headerType);
        var $realHeaderText = $(selectors.headerText);
        var $realBody = $(selectors.bodyTemplate);
        var $realFooter = $(selectors.footerTemplate);
        var $realHeaderHandle = $(selectors.headerHandle);
        var $realHeaderImage = $(selectors.headerImage);
        var $realButtonsJson = $(selectors.buttonsJson);

        // Builder fields
        var $templateName = $(selectors.builderTemplateName);
        var $category = $(selectors.builderCategory);
        var $headerType = $(selectors.builderHeaderType);
        var $headerText = $(selectors.builderHeaderText);
        var $body = $(selectors.builderBody);
        var $footer = $(selectors.builderFooter);
        var $enableButtons = $('#wa-enable-buttons');
        var $buttonsContainer = $('#wa-buttons-container');

        // Preview elements
        var $previewHeader = $(selectors.previewHeader);
        var $previewMedia = $(selectors.previewMedia);
        var $previewBody = $(selectors.previewBody);
        var $previewFooter = $(selectors.previewFooter);
        var $previewButtons = $(selectors.previewButtons);

        // Media elements
        var $mediaUploadInput = $(selectors.mediaUploadInput);
        var $mediaUploadButton = $(selectors.mediaUploadButton);
        var $mediaUploadStatus = $(selectors.mediaUploadStatus);
        var $mediaPreview = $(selectors.mediaPreview);

        // Section containers
        var $mediaSection = $(selectors.mediaSection);
        var $headerTextSection = $(selectors.headerTextSection);

        // Buttons
        var $addButtonRow = $(selectors.addButtonRow);
        var $buttonsRows = $(selectors.buttonsRows);

        // Actions
        var $saveTemplateButton = $(selectors.saveTemplateButton);
        var $saveTemplateStatus = $(selectors.saveTemplateStatus);

        var lastSelectionStart = 0;
        var lastSelectionEnd = 0;

        function hideNativeRows() {
            [
                selectors.templateName,
                selectors.category,
                selectors.language,
                selectors.headerType,
                selectors.headerText,
                '#row_whatsapp_template_order_template_header_media',
                selectors.headerHandle,
                selectors.headerImage,
                selectors.bodyTemplate,
                '#row_whatsapp_template_order_template_variable_selector',
                selectors.footerTemplate,
                '#row_whatsapp_template_order_template_buttons_builder',
                selectors.buttonsJson,
                '#row_whatsapp_template_order_template_save_template'
            ].forEach(function (selector) {
                $(selector).closest('tr').hide();
            });

            $('#row_whatsapp_template_order_template_live_preview .label').hide();
            $('#row_whatsapp_template_order_template_live_preview .value').css({
                width: '100%',
                float: 'none'
            });
        }

        function syncRealFields() {
            $realTemplateName.val($templateName.val());
            $realCategory.val($category.val());
            $realHeaderType.val($headerType.val()).trigger('change');
            $realHeaderText.val($headerText.val());
            $realBody.val($body.val());
            $realFooter.val($footer.val());

            var buttons = getButtonsData();
            $realButtonsJson.val(JSON.stringify(buttons));
        }

        function rememberCursor() {
            lastSelectionStart = $body.prop('selectionStart') || 0;
            lastSelectionEnd = $body.prop('selectionEnd') || lastSelectionStart;
        }

        function insertAtCursor(token) {
            var value = $body.val();
            var start = lastSelectionStart;
            var end = lastSelectionEnd;

            if (token.indexOf('{{') === -1) {
                token = '{{' + token + '}}';
            }

            var nextValue = value.substring(0, start) + token + value.substring(end);

            $body.val(nextValue);
            $body.focus();

            var newPos = start + token.length;
            $body.prop('selectionStart', newPos);
            $body.prop('selectionEnd', newPos);

            rememberCursor();
            syncRealFields();
            updatePreview();
        }

        function toggleHeaderSections() {
            var type = $headerType.val() || 'none';
            $headerTextSection.toggle(type === 'text');
            $mediaSection.toggle(type === 'image');
        }

        function toggleButtonsSection() {
            var enabled = $enableButtons.is(':checked');
            $buttonsContainer.toggle(enabled);
            updatePreview();
        }

        function getButtonsData() {
            if (!$enableButtons.is(':checked')) {
                return [];
            }

            var rows = [];
            $buttonsRows.find('.wa-button-row').each(function (index) {
                if (index >= 3) return false;

                var $row = $(this);
                var type = $row.find('.wa-button-type').val();
                var text = $.trim($row.find('.wa-button-text').val());
                var value = $.trim($row.find('.wa-button-value').val());

                if (!type || !text) return;

                var btn = { type: type, text: text };
                if (type === 'URL') btn.button_url = value;
                if (type === 'PHONE_NUMBER') btn.phone_number = value;

                rows.push(btn);
            });

            return rows;
        }

        function renderButtonsPreview() {
            var html = '';
            var buttons = getButtonsData();

            $.each(buttons, function (index, button) {
                html += '<span class="wa-preview-button">' + $('<div/>').text(button.text).html() + '</span>';
            });

            $previewButtons.html(html).toggle(html !== '');
        }

        function updatePreview() {
            var headerType = $headerType.val() || 'none';
            var resolvedHeader = resolveTemplate($headerText.val(), sampleData);
            var resolvedBody = resolveTemplate($body.val(), sampleData);
            var resolvedFooter = resolveTemplate($footer.val(), sampleData);
            var imageUrl = $realHeaderImage.val();

            if (headerType === 'text' && resolvedHeader) {
                $previewHeader.text(resolvedHeader).show();
            } else {
                $previewHeader.hide();
            }

            if (headerType === 'image') {
                if (imageUrl) {
                    $previewMedia.html('<img src="' + imageUrl + '" alt="Header Preview"/>').show();
                    $mediaPreview.html('<img src="' + imageUrl + '" alt="Header Upload Preview"/>');
                } else {
                    $previewMedia.text('Image Header Preview').show();
                    $mediaPreview.empty();
                }
            } else {
                $previewMedia.hide().empty();
                $mediaPreview.empty();
            }

            $previewBody.text(resolvedBody || 'Your preview appears here.');

            if (resolvedFooter) {
                $previewFooter.text(resolvedFooter).show();
            } else {
                $previewFooter.hide();
            }

            renderButtonsPreview();
        }

        function toggleButtonFields($row) {
            var type = $row.find('.wa-button-type').val();
            var $value = $row.find('.wa-button-value');

            if (type === 'URL') {
                $value.attr('placeholder', 'https://example.com').show();
            } else if (type === 'PHONE_NUMBER') {
                $value.attr('placeholder', '+1234567890').show();
            } else {
                $value.hide();
            }
        }

        function addButtonRow(data) {
            if ($buttonsRows.find('.wa-button-row').length >= 3) {
                alert('Maximum 3 buttons allowed.');
                return;
            }

            data = data || {};
            var html = '' +
                '<div class="wa-button-row">' +
                    '<select class="admin__control-select wa-button-type">' +
                        '<option value="">Select Type</option>' +
                        '<option value="QUICK_REPLY">Quick Reply</option>' +
                        '<option value="URL">URL Button</option>' +
                        '<option value="PHONE_NUMBER">Phone Button</option>' +
                    '</select>' +
                    '<input type="text" class="admin__control-text wa-button-text" placeholder="Button Label"/>' +
                    '<input type="text" class="admin__control-text wa-button-value" style="display:none;"/>' +
                    '<button type="button" class="action-delete wa-remove-button-row"><span>Remove</span></button>' +
                '</div>';
            var $row = $(html);

            $row.find('.wa-button-type').val(data.type || '');
            $row.find('.wa-button-text').val(data.text || '');
            $row.find('.wa-button-value').val(data.button_url || data.phone_number || '');

            $buttonsRows.append($row);
            toggleButtonFields($row);
            syncRealFields();
            updatePreview();
        }

        function uploadHeaderMedia() {
            var file = $mediaUploadInput[0].files[0];
            if (!file) {
                $mediaUploadStatus.html('<div class="message message-error error"><div>Select an image first.</div></div>');
                return;
            }

            var formData = new FormData();
            formData.append('form_key', window.FORM_KEY);
            formData.append('header_media_file', file);

            $.ajax({
                url: config.uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                showLoader: true
            }).done(function (response) {
                if (!response.document_id) {
                    $mediaUploadStatus.html('<div class="message message-error error"><div>' +
                        (response.error || 'Upload failed.') + '</div></div>');
                    return;
                }

                $realHeaderHandle.val(response.document_id);
                $realHeaderImage.val(response.preview_link || response.url || '');
                $mediaUploadStatus.html('<div class="message message-success success"><div>Image uploaded successfully.</div></div>');
                updatePreview();
            }).fail(function () {
                $mediaUploadStatus.html('<div class="message message-error error"><div>Unable to upload image.</div></div>');
            });
        }

        function saveTemplate() {
            syncRealFields();

            var templateName = $.trim($templateName.val());
            var bodyRaw = $.trim($body.val());

            if (!templateName) {
                alert('Please enter a template name.');
                $templateName.focus();
                return;
            }

            if (!bodyRaw) {
                alert('Please enter message body.');
                $body.focus();
                return;
            }

            // Process variables for Meta API (Requirement 7)
            var bodyProcessed = processTemplateVariables(bodyRaw, sampleData);
            var headerProcessed = processTemplateVariables($.trim($headerText.val()), sampleData);

            $.ajax({
                url: config.saveTemplateUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    form_key: window.FORM_KEY,
                    store_id: config.storeId || 0,
                    event_code: $('#whatsapp_template_order_template_event_code').val(),
                    template_name: templateName,
                    category: $category.val(),
                    language: $realLanguage.val(),
                    header_type: $headerType.val(),
                    header_text: headerProcessed.text,
                    header_handle: $realHeaderHandle.val(),
                    header_image: $realHeaderImage.val(),
                    body_template: bodyProcessed.text,
                    body_examples_json: JSON.stringify(bodyProcessed.examples),
                    footer_template: $.trim($footer.val()),
                    buttons_json: $realButtonsJson.val()
                }
            }).done(function (response) {
                var typeClass = response.success ? 'message-success success' : 'message-error error';
                $saveTemplateStatus.html(
                    '<div class="messages"><div class="message ' + typeClass + '"><div>' +
                    response.message + (response.template_id ? '<br/><small>ID: ' + response.template_id + '</small>' : '') +
                    '</div></div></div>'
                );
            }).fail(function () {
                $saveTemplateStatus.html('<div class="messages"><div class="message message-error error"><div>Unable to save template.</div></div></div>');
            });
        }

        // Init
        hideNativeRows();
        toggleHeaderSections();
        toggleButtonsSection();

        var initialButtons = parseJson($realButtonsJson.val(), []);
        if (initialButtons.length > 0) {
            $enableButtons.prop('checked', true);
            $buttonsContainer.show();
            $.each(initialButtons, function(i, btn) { addButtonRow(btn); });
        }

        // Event listeners
        $templateName.on('input', syncRealFields);
        $category.on('change', syncRealFields);
        $headerType.on('change', function () {
            toggleHeaderSections();
            syncRealFields();
            updatePreview();
        });
        $headerText.on('input', function () {
            syncRealFields();
            updatePreview();
        });
        $body.on('click keyup focus input', function () {
            rememberCursor();
            syncRealFields();
            updatePreview();
        });
        $footer.on('input', function () {
            syncRealFields();
            updatePreview();
        });
        $('.wa-variable-badge').on('click', function() {
            insertAtCursor($(this).data('value'));
        });
        $enableButtons.on('change', toggleButtonsSection);
        $mediaUploadButton.on('click', uploadHeaderMedia);
        $addButtonRow.on('click', function() { addButtonRow(); });
        $saveTemplateButton.on('click', saveTemplate);

        $(document).on('change', '.wa-button-type', function () {
            toggleButtonFields($(this).closest('.wa-button-row'));
            syncRealFields();
            updatePreview();
        });
        $(document).on('input', '.wa-button-text, .wa-button-value', function () {
            syncRealFields();
            updatePreview();
        });
        $(document).on('click', '.wa-remove-button-row', function () {
            $(this).closest('.wa-button-row').remove();
            syncRealFields();
            updatePreview();
        });
    };
});
