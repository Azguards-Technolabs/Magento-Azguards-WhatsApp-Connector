define([
    'jquery'
], function ($) {
    'use strict';

    function extractValue(data, path) {
        var value = data[path];
        var segments;
        var i;

        if (typeof value !== 'undefined') {
            return value;
        }

        segments = path.split('.');
        value = data;

        for (i = 0; i < segments.length; i += 1) {
            if (typeof value !== 'object' || value === null || typeof value[segments[i]] === 'undefined') {
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

            if (typeof value === 'undefined' || value === null || typeof value === 'object') {
                return '';
            }

            return String(value);
        });
    }

    function resolveTemplate(template, data) {
        var rendered = template || '';
        var items = data.items || [];

        rendered = rendered.replace(/\{\{\#items\}\}([\s\S]*?)\{\{\/items\}\}/g, function (match, rowTemplate) {
            var rows = [];

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

    return function (config) {
        var selectors = config.selectors || {};
        var sampleData = config.sampleData || {};
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
        var $templateName = $(selectors.builderTemplateName);
        var $category = $(selectors.builderCategory);
        var $language = $(selectors.builderLanguage);
        var $headerType = $(selectors.builderHeaderType);
        var $headerText = $(selectors.builderHeaderText);
        var $body = $(selectors.builderBody);
        var $footer = $(selectors.builderFooter);
        var $variableSelect = $(selectors.builderVariableSelect);
        var $previewHeader = $(selectors.previewHeader);
        var $previewMedia = $(selectors.previewMedia);
        var $previewBody = $(selectors.previewBody);
        var $previewFooter = $(selectors.previewFooter);
        var $previewButtons = $(selectors.previewButtons);
        var $mediaUploadInput = $(selectors.mediaUploadInput);
        var $mediaUploadButton = $(selectors.mediaUploadButton);
        var $mediaUploadStatus = $(selectors.mediaUploadStatus);
        var $mediaPreview = $(selectors.mediaPreview);
        var $mediaSection = $(selectors.mediaSection);
        var $headerTextSection = $(selectors.headerTextSection);
        var $addButtonRow = $(selectors.addButtonRow);
        var $buttonsRows = $(selectors.buttonsRows);
        var $saveTemplateButton = $(selectors.saveTemplateButton);
        var $saveTemplateStatus = $(selectors.saveTemplateStatus);
        var lastSelectionStart = 0;
        var lastSelectionEnd = 0;

        function hideNativeRows() {
            [
                '#row_whatsapp_template_order_template_template_name',
                '#row_whatsapp_template_order_template_category',
                '#row_whatsapp_template_order_template_language',
                '#row_whatsapp_template_order_template_header_type',
                '#row_whatsapp_template_order_template_header_text',
                '#row_whatsapp_template_order_template_header_media',
                '#row_whatsapp_template_order_template_body_template',
                '#row_whatsapp_template_order_template_variable_selector',
                '#row_whatsapp_template_order_template_footer_template',
                '#row_whatsapp_template_order_template_buttons_builder',
                '#row_whatsapp_template_order_template_save_template'
            ].forEach(function (selector) {
                $(selector).hide();
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
            $realLanguage.val($language.val());
            $realHeaderType.val($headerType.val()).trigger('change');
            $realHeaderText.val($headerText.val());
            $realBody.val($body.val());
            $realFooter.val($footer.val());
            $realButtonsJson.val(JSON.stringify(getButtonsData()));
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
            $body.prop('selectionStart', start + token.length);
            $body.prop('selectionEnd', start + token.length);
            rememberCursor();
            syncRealFields();
            updatePreview();
        }

        function toggleHeaderSections() {
            var type = $headerType.val() || 'none';

            $headerTextSection.stop(true, true).toggle(type === 'text');
            $mediaSection.stop(true, true).toggle(type === 'image');
        }

        function getButtonsData() {
            var rows = [];

            $buttonsRows.find('.wa-button-row').each(function () {
                var $row = $(this);
                var type = $row.find('.wa-button-type').val();
                var text = $.trim($row.find('.wa-button-text').val());
                var value = $.trim($row.find('.wa-button-value').val());
                var coupon = $.trim($row.find('.wa-button-coupon').val());

                if (!type || !text) {
                    return;
                }

                rows.push({
                    type: type,
                    text: text,
                    value: value,
                    coupon_code: coupon
                });
            });

            return rows;
        }

        function renderButtonsPreview() {
            var html = '';

            $.each(getButtonsData(), function (index, button) {
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

            $previewHeader.text(headerType === 'text' ? resolvedHeader : '').toggle(headerType === 'text' && resolvedHeader !== '');
            $previewBody.text(resolvedBody || 'Your preview appears here.');
            $previewFooter.text(resolvedFooter || '').toggle(resolvedFooter !== '');

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

            renderButtonsPreview();
        }

        function toggleButtonFields($row) {
            var type = $row.find('.wa-button-type').val();
            var $value = $row.find('.wa-button-value');
            var $coupon = $row.find('.wa-button-coupon');

            $value.toggle(type === 'URL' || type === 'PHONE_NUMBER');
            $coupon.toggle(type === 'COPY_CODE');
        }

        function addButtonRow(button) {
            var data = button || {};
            var html = '' +
                '<div class="wa-button-row">' +
                    '<select class="admin__control-select wa-button-type">' +
                        '<option value="">None</option>' +
                        '<option value="QUICK_REPLY">Quick Reply</option>' +
                        '<option value="URL">URL</option>' +
                        '<option value="PHONE_NUMBER">Phone Number</option>' +
                        '<option value="COPY_CODE">Copy Code</option>' +
                    '</select>' +
                    '<input type="text" class="admin__control-text wa-button-text" placeholder="Button text"/>' +
                    '<input type="text" class="admin__control-text wa-button-value" placeholder="URL / Phone / Value"/>' +
                    '<input type="text" class="admin__control-text wa-button-coupon" placeholder="Coupon code"/>' +
                    '<button type="button" class="action-delete wa-remove-button-row"><span>Remove</span></button>' +
                '</div>';
            var $row = $(html);

            $row.find('.wa-button-type').val(data.type || '');
            $row.find('.wa-button-text').val(data.text || '');
            $row.find('.wa-button-value').val(data.value || '');
            $row.find('.wa-button-coupon').val(data.coupon_code || '');

            $buttonsRows.append($row);
            toggleButtonFields($row);
            syncRealFields();
            updatePreview();
        }

        function loadStoredButtons() {
            $.each(parseJson($realButtonsJson.val() || '[]', []), function (index, button) {
                addButtonRow(button);
            });
        }

        function uploadHeaderMedia() {
            var file = $mediaUploadInput[0].files[0];
            var formData;

            if (!file) {
                $mediaUploadStatus.html('<div class="message message-error error"><div>Select an image first.</div></div>');
                return;
            }

            formData = new FormData();
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

            $.ajax({
                url: config.saveTemplateUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    form_key: window.FORM_KEY,
                    store_id: config.storeId || 0,
                    event_code: $('#whatsapp_template_order_template_event_code').val(),
                    template_name: $realTemplateName.val(),
                    category: $realCategory.val(),
                    language: $realLanguage.val(),
                    header_type: $realHeaderType.val(),
                    header_text: $realHeaderText.val(),
                    header_handle: $realHeaderHandle.val(),
                    header_image: $realHeaderImage.val(),
                    body_template: $realBody.val(),
                    footer_template: $realFooter.val(),
                    buttons_json: $realButtonsJson.val()
                }
            }).done(function (response) {
                var typeClass = response.success ? 'message-success success' : 'message-error error';
                var extra = response.success && response.template_id
                    ? '<br/><small>Meta Template ID: ' + $('<div/>').text(response.template_id).html() + '</small>'
                    : '';

                $saveTemplateStatus.html(
                    '<div class="messages"><div class="message ' + typeClass + '"><div>' +
                    response.message + extra +
                    '</div></div></div>'
                );
            }).fail(function () {
                $saveTemplateStatus.html('<div class="messages"><div class="message message-error error"><div>Unable to save template.</div></div></div>');
            });
        }

        hideNativeRows();
        toggleHeaderSections();
        loadStoredButtons();
        syncRealFields();
        updatePreview();

        $templateName.on('input', function () {
            syncRealFields();
        });
        $category.on('change', function () {
            syncRealFields();
        });
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
        $variableSelect.on('change', function () {
            if ($(this).val()) {
                insertAtCursor($(this).val());
                $(this).val('');
            }
        });
        $mediaUploadButton.on('click', uploadHeaderMedia);
        $addButtonRow.on('click', function () {
            addButtonRow();
        });
        $saveTemplateButton.on('click', saveTemplate);

        $(document).on('change', '.wa-button-type', function () {
            toggleButtonFields($(this).closest('.wa-button-row'));
            syncRealFields();
            updatePreview();
        });
        $(document).on('input', '.wa-button-text, .wa-button-value, .wa-button-coupon', function () {
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
