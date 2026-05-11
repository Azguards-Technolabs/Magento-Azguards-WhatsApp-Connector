/* phpcs:ignoreFile */
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
            getBillingAddress: function () {
                return {
                    getStreetLine: function () { return '123 Business Bay'; },
                    getCity: function () { return 'Dubai'; }
                };
            },
            getPayment: function () {
                return {
                    getMethodInstance: function () {
                        return { getTitle: function () { return 'Bank Transfer Payment'; } };
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
                args = argMatch[2].split(',').map(function (s) { return s.trim().replace(/['"]/g, ''); });
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

    return function (config, element) {
        var $element = $(element);
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
        var $templateName = $element.find(config.selectors.builderTemplateName);
        var $category = $element.find(config.selectors.builderCategory);
        var $headerType = $element.find(config.selectors.builderHeaderType);
        var $headerText = $element.find(config.selectors.builderHeaderText);
        var $body = $element.find(config.selectors.builderBody);
        var $footer = $element.find(config.selectors.builderFooter);
        var $enableButtons = $element.find('.wa-enable-buttons');
        var $buttonsContainer = $element.find('.wa-buttons-container');

        // Preview elements
        var $previewHeader = $element.find(config.selectors.previewHeader);
        var $previewMedia = $element.find(config.selectors.previewMedia);
        var $previewBody = $element.find(config.selectors.previewBody);
        var $previewFooter = $element.find(config.selectors.previewFooter);
        var $previewButtons = $element.find(config.selectors.previewButtons);

        // Media elements
        var $mediaUploadInput = $element.find(config.selectors.mediaUploadInput);
        var $mediaUploadButton = $element.find(config.selectors.mediaUploadButton);
        var $mediaUploadStatus = $element.find(config.selectors.mediaUploadStatus);
        var $mediaPreview = $element.find(config.selectors.mediaPreview);

        // Section containers
        var $mediaSection = $element.find(config.selectors.mediaSection);
        var $headerTextSection = $element.find(config.selectors.headerTextSection);

        // Buttons
        var $addButtonRow = $element.find(config.selectors.addButtonRow);
        var $buttonsRows = $element.find(config.selectors.buttonsRows);

        // Actions
        var $saveTemplateButton = $element.find(config.selectors.saveTemplateButton);
        var $saveTemplateStatus = $element.find(config.selectors.saveTemplateStatus);

        var isExistingTemplate = !!(config.initialConfig && config.initialConfig.external_id);
        if (isExistingTemplate) {
            $saveTemplateButton.find('span').text('Update Meta Template');
        }

        var lastSelectionStart = 0;
        var lastSelectionEnd = 0;

        function hideNativeRows() {
            // Determine section and group dynamically from headerType selector
            // Pattern: #sectionId_groupName_header_type
            var parts = config.selectors.headerType.substring(1).split('_');
            // Reconstruct based on expected suffixes
            // We know the last two are 'header' and 'type'
            var headerTypeSuffix = '_header_type';
            var fullId = config.selectors.headerType.substring(1);
            var groupWithSection = fullId.substring(0, fullId.length - headerTypeSuffix.length);

            // The first section can be whatsapp_abandoned_cart or whatsApp_conector
            var sectionId = '';
            var groupName = '';

            if (fullId.indexOf('whatsapp_abandoned_cart_') === 0) {
                sectionId = 'whatsapp_abandoned_cart';
                groupName = fullId.substring('whatsapp_abandoned_cart_'.length, fullId.length - headerTypeSuffix.length);
            } else if (fullId.indexOf('whatsApp_conector_') === 0) {
                sectionId = 'whatsApp_conector';
                groupName = fullId.substring('whatsApp_conector_'.length, fullId.length - headerTypeSuffix.length);
            } else {
                // Fallback to simple split if unknown section
                sectionId = parts[0];
                groupName = parts.slice(1, -2).join('_');
            }

            var prefix = '#' + sectionId + '_' + groupName + '_';
            var rowPrefix = '#row_' + sectionId + '_' + groupName + '_';

            [
                selectors.templateName,
                selectors.category,
                selectors.language,
                selectors.headerType,
                selectors.headerText,
                rowPrefix + 'header_media',
                selectors.headerHandle,
                selectors.headerImage,
                selectors.bodyTemplate,
                rowPrefix + 'variable_selector',
                rowPrefix + 'order_create_variable',
                rowPrefix + 'order_invoice_variable',
                rowPrefix + 'order_shipment_variable',
                rowPrefix + 'order_cancellation_variable',
                rowPrefix + 'order_credit_memo_variable',
                selectors.footerTemplate,
                rowPrefix + 'buttons_builder',
                selectors.buttonsJson,
                rowPrefix + 'save_template'
            ].forEach(function (selector) {
                $(selector).closest('tr').hide();
            });

            $(rowPrefix + 'live_preview .label').hide();
            $(rowPrefix + 'live_preview .value').css({
                width: '100%',
                float: 'none'
            });
        }

        function syncRealFields() {
            if ($templateName.length && $templateName.val()) {
                $realTemplateName.val($templateName.val());
            }
            if ($category.length && $category.val()) {
                $realCategory.val($category.val());
            }
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
                html += '<span class="wa-preview-button">' + $('<div>').text(btnLabel).html() + '</span>';
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
                '<input type="text" class="admin__control-text wa-button-text" placeholder="Button Label">' +
                '<input type="text" class="admin__control-text wa-button-value" style="display:none;">' +
                '<button type="button" class="action-delete wa-remove-button-row"><span>Remove</span></button>' +
                '</div>';
            var $row = $(html);

            $row.find('.wa-button-type').val(data.type || 'QUICK_REPLY');
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

            var templateName = $.trim($realTemplateName.val());
            var bodyRaw = $.trim($body.val());

            if (!templateName) {
                alert('Please enter a template name.');
                if ($templateName.length) {
                    $templateName.focus();
                }
                return;
            }

            if (!bodyRaw) {
                alert('Please enter message body.');
                $body.focus();
                return;
            }

            var requestData = {
                form_key: window.FORM_KEY,
                store_id: config.storeId || 0,
                event_code: config.eventCode || $(config.selectors.eventCodeInput).val(),
                template_name: templateName,
                category: $realCategory.val(),
                language: $realLanguage.val(),
                header_type: $headerType.val(),
                header_text: $.trim($headerText.val()),
                header_handle: $realHeaderHandle.val(),
                header_image: $realHeaderImage.val(),
                body_template: bodyRaw,
                footer_template: $.trim($footer.val()),
                buttons_json: $realButtonsJson.val()
            };

            $saveTemplateStatus.html('<div class="messages"><div class="message message-notice notice"><div>' +
                (isExistingTemplate ? 'Updating template in Meta...' : 'Creating template in Meta...') +
                '</div></div></div>');

            $.ajax({
                url: config.saveTemplateUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: requestData
            }).done(function (response) {
                var typeClass = response.success ? 'message-success success' : 'message-error error';
                var actionLabel = isExistingTemplate ? 'Update' : 'Creation';

                $saveTemplateStatus.html(
                    '<div class="messages"><div class="message ' + typeClass + '"><div>' +
                    '<strong>' + actionLabel + ' Result:</strong> ' + response.message +
                    (response.template_id ? '<br><small>Meta ID: ' + response.template_id + '</small>' : '') +
                    '</div></div></div>'
                );

                if (response.success) {
                    $saveTemplateStatus.append('<div style="margin-top:10px; color:green; font-weight:600;">Persisting configuration and refreshing...</div>');

                    // Update internal state
                    isExistingTemplate = true;
                    $saveTemplateButton.find('span').text('Update Meta Template');

                    setTimeout(function () {
                        if ($('#config-edit-form').length) {
                            $('#config-edit-form').submit();
                        } else if ($('#save').length) {
                            $('#save').click();
                        }
                    }, 1200);
                }
            }).fail(function () {
                $saveTemplateStatus.html('<div class="messages"><div class="message message-error error"><div>Unable to communicate with the WhatsApp Template Service.</div></div></div>');
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
            $.each(initialButtons, function (i, btn) { addButtonRow(btn); });
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
        var $varBtn = $element.find('.wa-insert-variable-btn');
        var $varMenu = $element.find('.wa-variable-menu');

        $varBtn.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $varMenu.toggle();
        });

        $element.find('.wa-var-item').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var val = $(this).data('val');
            if (val) {
                insertAtCursor(val);
            }
            $varMenu.hide();
        });

        $element.find('.wa-var-tab-btn').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var targetId = $(this).data('target');

            $element.find('.wa-var-tab-btn').removeClass('active');
            $(this).addClass('active');

            $element.find('.wa-var-panel').removeClass('active');
            $element.find('#' + targetId).addClass('active');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.wa-variable-inserter').length) {
                $varMenu.hide();
            }
        });
        $enableButtons.on('change', toggleButtonsSection);
        $mediaUploadButton.on('click', uploadHeaderMedia);
        $addButtonRow.on('click', function () { addButtonRow(); });
        $saveTemplateButton.on('click', saveTemplate);

        $element.on('change', '.wa-button-type', function () {
            toggleButtonFields($(this).closest('.wa-button-row'));
            syncRealFields();
            updatePreview();
        });
        $element.on('input', '.wa-button-text, .wa-button-value', function () {
            syncRealFields();
            updatePreview();
        });
        $element.on('click', '.wa-remove-button-row', function () {
            $(this).closest('.wa-button-row').remove();
            syncRealFields();
            updatePreview();
        });
    };
});
