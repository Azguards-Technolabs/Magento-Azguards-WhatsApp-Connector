define([
    'jquery'
], function ($) {
    'use strict';

    var sampleData = {
        order: {
            increment_id: '#10001',
            status: 'Processing',
            created_at: '2023-10-27 10:00:00',
            total_qty_ordered: '3',
            customer_firstname: 'Zubair',
            customer_lastname: 'Sayed',
            customer_email: 'zubair@example.com',
            grand_total: '$150.00',
            subtotal: '$140.00',
            discount_amount: '$0.00',
            tax_amount: '$10.00',
            shipping_description: 'Flat Rate - Fixed',
            shipping_amount: '$5.00',
            getBillingAddress: function() {
                return {
                    getStreetLine: function() { return '123 Business Bay'; },
                    getCity: function() { return 'Dubai'; }
                };
            },
            getPayment: function() {
                return {
                    getMethodInstance: function() {
                        return { getTitle: function() { return 'Bank Transfer Payment'; } };
                    }
                };
            }
        },
        items: [
            { name: 'Classic T-Shirt', qty: '1', price: '$50.00' },
            { name: 'Blue Jeans', qty: '1', price: '$60.00' },
            { name: 'Baseball Cap', qty: '1', price: '$40.00' }
        ]
    };

    function extractValue(data, path) {
        var segments = path.split('.');
        var value = data;

        for (var i = 0; i < segments.length; i++) {
            var segment = segments[i];
            var isMethod = segment.indexOf('()') !== -1;
            var key = isMethod ? segment.replace('()', '') : segment;

            var argMatch = key.match(/(.*)\((.*)\)/);
            var args = [];
            if (argMatch) {
                key = argMatch[1];
                args = argMatch[2].split(',').map(function(s) { return s.trim().replace(/['"]/g, ''); });
                isMethod = true;
            }

            if (value === null || typeof value !== 'object' || typeof value[key] === 'undefined') {
                return '';
            }

            if (isMethod && typeof value[key] === 'function') {
                value = value[key].apply(value, args);
            } else {
                value = value[key];
            }
        }

        return value;
    }

    function replaceVariables(template, data) {
        return (template || '').replace(/\{\{\s*(?:var\s+)?([a-zA-Z0-9_.()]+)\s*\}\}/g, function (match, path) {
            if (path.indexOf('#items') !== -1 || path.indexOf('/items') !== -1) {
                return match;
            }

            var value = extractValue(data, path);
            return (value !== undefined && value !== null) ? String(value) : match;
        });
    }

    function resolveTemplate(template, data) {
        var rendered = template || '';

        rendered = rendered.replace(/\{\{\#items\}\}([\s\S]*?)\{\{\/items\}\}/g, function (match, rowTemplate) {
            var rows = [];
            var items = data.items || [];

            $.each(items, function (index, item) {
                rows.push(replaceVariables(rowTemplate, { items: item }));
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

    return function (config) {
        var selectors = config.selectors || {};

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
        var $templateName = $('#wa-builder-template-name');
        var $category = $('#wa-builder-category');
        var $headerType = $('#wa-builder-header-type');
        var $headerText = $('#wa-builder-header-text');
        var $body = $('#wa-builder-body');
        var $footer = $('#wa-builder-footer');
        var $enableButtons = $('#wa-enable-buttons');
        var $buttonsContainer = $('#wa-buttons-container');

        // Preview elements
        var $previewHeader = $('[data-role="wa-preview-header"]');
        var $previewMedia = $('[data-role="wa-preview-media"]');
        var $previewBody = $('[data-role="wa-preview-body"]');
        var $previewFooter = $('[data-role="wa-preview-footer"]');
        var $previewButtons = $('[data-role="wa-preview-buttons"]');

        // Media elements
        var $mediaUploadInput = $('#wa-header-media-file');
        var $mediaUploadButton = $('#wa-header-media-upload');
        var $mediaUploadStatus = $('#wa-header-media-status');
        var $mediaPreview = $('[data-role="wa-header-media-preview"]');

        // Section containers
        var $mediaSection = $('#wa-builder-header-media-section');
        var $headerTextSection = $('#wa-builder-header-text-section');

        // Buttons
        var $addButtonRow = $('#wa-add-button-row');
        var $buttonsRows = $('#wa-buttons-rows');

        // Actions
        var $saveTemplateButton = $('#wa-save-template');
        var $saveTemplateStatus = $('#wa-save-template-status');

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
                var btnLabel = resolveTemplate(button.text, sampleData);
                html += '<span class="wa-preview-button">' + $('<div/>').text(btnLabel).html() + '</span>';
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
                $value.attr('placeholder', 'URL (e.g. https://track.me/{{var order.increment_id}})').show();
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
                    header_text: $.trim($headerText.val()),
                    header_handle: $realHeaderHandle.val(),
                    header_image: $realHeaderImage.val(),
                    body_template: bodyRaw,
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
