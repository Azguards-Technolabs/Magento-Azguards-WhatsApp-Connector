define([
    'jquery',
    'uiRegistry',
    'mage/url',
    'mage/translate',
    'select2'
], function ($, registry, urlBuilder, $t) {
    'use strict';

    function parseIds(value) {
        if (!value) {
            return [];
        }
        if (Array.isArray(value)) {
            return value.map(String).filter(Boolean);
        }
        if (typeof value === 'string') {
            return value.split(',').map(function (v) { return v.trim(); }).filter(Boolean);
        }
        return [];
    }

    function safeParseJson(value) {
        if (!value) {
            return {};
        }
        if (typeof value === 'object') {
            return value;
        }
        if (typeof value !== 'string') {
            return {};
        }
        try {
            var parsed = JSON.parse(value);
            return (parsed && typeof parsed === 'object') ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function initialsFromName(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) {
            return '??';
        }
        if (parts.length === 1) {
            return parts[0].slice(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function renderContactOption(item) {
        if (!item || item.loading) {
            return item && item.text ? item.text : '';
        }

        var name = item.name || item.text || '';
        var email = item.email || '';
        var phone = item.phone || '';

        // Prioritize email if name is missing or placeholder-ish
        if (!name || name === '??') {
            name = email || phone || ($t('Customer #') + item.id);
        }

        var avatar = initialsFromName(name);
        var isSelected = item.selected ? 'checked' : '';

        // Build sub-text: "email | phone"
        var subParts = [];
        if (email) subParts.push(email);
        if (phone) subParts.push(phone);
        var subText = subParts.join(' | ');

        return $(
            '<div class="wa-contact-option">' +
            '<span class="wa-contact-avatar"></span>' +
            '<span class="wa-contact-meta">' +
            '<div class="wa-contact-name"></div>' +
            '<div class="wa-contact-sub"></div>' +
            '</span>' +
            '<div class="wa-contact-checkbox">' +
            '<input type="checkbox" ' + isSelected + ' disabled style="pointer-events:none">' +
            '</div>' +
            '</div>'
        )
            .find('.wa-contact-avatar').text(avatar).end()
            .find('.wa-contact-name').text(name).end()
            .find('.wa-contact-sub').text(subText).end();
    }

    return function (config) {
        var selectors = {
            contactsWrapper: config.contactsWrapper || '#campaign_custom_contacts_wrapper',
            contactsSelect: config.contactsSelect || '#campaign_custom_contacts',
            headerPreview: config.headerPreview || '#campaign-header-preview',
            variablesContainer: config.variablesContainer || '#campaign-variable-mapping-container'
        };

        var ajax = {
            customers: urlBuilder.build('whatsappconnect/campaign/searchcustomers'),
            templateVars: urlBuilder.build('whatsappconnect/campaign/gettemplatevariables')
        };

        function setContactsVisible(isVisible) {
            console.log('WhatsApp Campaign JS: Setting contacts visibility to: ' + isVisible);
            if (isVisible) {
                $(selectors.contactsWrapper).removeClass('wa-hidden').show();
            } else {
                $(selectors.contactsWrapper).addClass('wa-hidden').hide();
            }
        }

        function renderHeaderPreview(headerFormat, headerImage) {
            var $preview = $(selectors.headerPreview);
            $preview.empty();

            var format = String(headerFormat || '').toUpperCase();
            if (!format || format === 'TEXT') {
                $preview.hide();
                return;
            }

            $preview.show();

            if (format === 'IMAGE' && headerImage) {
                $preview.append(
                    '<div class="wa-header-preview">' +
                    '<div><strong>' + $t('Header Media (IMAGE)') + '</strong></div>' +
                    '<div style="color:#5f5f5f;font-size:12px;margin-top:4px;">' + $t('Media Uploaded (from selected template)') + '</div>' +
                    '<img src="' + $('<div>').text(headerImage).html() + '" alt="Header">' +
                    '</div>'
                );
                return;
            }

            $preview.append(
                '<div class="wa-header-preview">' +
                '<div><strong>' + $t('Header Media') + '</strong></div>' +
                '<div style="color:#5f5f5f;font-size:12px;margin-top:4px;">' + $t('Format:') + ' ' + $('<div>').text(format).html() + '</div>' +
                '</div>'
            );
        }

        function renderVariables(variables, mapping) {
            console.log('WhatsApp Campaign JS: Rendering variables. Count: ' + (variables ? variables.length : 0));
            var $container = $(selectors.variablesContainer);
            $container.empty();
            $container.removeClass('wa-hidden');

            if (!variables || !variables.length) {
                console.log('WhatsApp Campaign JS: No variables to render.');
                $container.hide();
                return;
            }

            console.log('WhatsApp Campaign JS: Showing variables container.');
            $container.show();
            var $wrap = $('<div class="wa-variable-section"></div>');
            $wrap.append('<h4 class="wa-section-title">' + $t('Template Setup') + '</h4>');
            $wrap.append('<p class="wa-section-hint">' + $t('Enter values for each body variable. If left blank, customer data will be used automatically where possible.') + '</p>');

            variables.forEach(function (variable) {
                var key = String(variable);
                var savedVal = (mapping && Object.prototype.hasOwnProperty.call(mapping, key)) ? mapping[key] : '';
                var $row = $('<div class="wa-variable-row"></div>');
                $row.append(
                    '<div class="label">' +
                    '<span>Value ⇌</span>' +
                    '<span class="var-name">{{' + $('<div>').text(key).html() + '}}</span>' +
                    '</div>'
                );
                $row.append(
                    '<div class="control">' +
                    '<input type="text" class="wa-variable-input" data-varname="' + $('<div>').text(key).html() + '" ' +
                    'placeholder="' + $t('Enter value for {{') + $('<div>').text(key).html() + $t('}}') + '" ' +
                    'value="' + $('<div>').text(savedVal || '').html() + '">' +
                    '</div>'
                );
                $wrap.append($row);
            });

            $container.append($wrap);
        }

        function syncVariableMapping(mappingField) {
            var payload = {};
            $(selectors.variablesContainer).find('.wa-variable-input').each(function () {
                var varName = $(this).data('varname');
                if (varName) {
                    payload[String(varName)] = $(this).val();
                }
            });
            mappingField.value(JSON.stringify(payload));
        }

        $(function () {
            console.log('WhatsApp Campaign JS: DOM Ready. Initializing Custom UI...');
            var $contacts = $(selectors.contactsSelect);

            // Advanced lookup for UI components to avoid collisions with shadow components
            function findComponent(index, callback) {
                console.log('WhatsApp Campaign JS: Searching for component: ' + index);
                registry.get('index = ' + index, function (component) {
                    if (!component) {
                        console.log('WhatsApp Campaign JS: Component ' + index + ' not found in registry.');
                        return;
                    }
                    console.log('WhatsApp Campaign JS: Found component index ' + index + ' (Full name: ' + component.name + ')');
                    // Check if this component is likely the one we want (part of our form or has value)
                    var isOurForm = component.name && component.name.indexOf('whatsapp_campaign_form') !== -1;
                    if (isOurForm) {
                        callback(component);
                    } else {
                        console.log('WhatsApp Campaign JS: Potential collision for ' + index + '. Failsafe matching...');
                        callback(component);
                    }
                });
            }

            findComponent('target_type', function (targetField) {
                var updateVisibility = function (val) {
                    // Some components might provide the value inside an object or as a raw string
                    var rawVal = (val && typeof val === 'object' && val.value !== undefined) ? val.value : val;
                    console.log('WhatsApp Campaign JS: target_type [' + targetField.name + '] value: ' + JSON.stringify(rawVal));

                    var isContacts = (rawVal === 'contacts');
                    setContactsVisible(isContacts);

                    if (isContacts) {
                        console.log('WhatsApp Campaign JS: Showing Specific Contacts UI.');
                    }
                };
                updateVisibility(targetField.value());
                targetField.on('update', updateVisibility);
            });

            findComponent('customer_ids', function (customerIdsField) {
                console.log('WhatsApp Campaign JS: Initializing Select2 for customer_ids');
                $contacts.select2({
                    placeholder: $t('Search and select contacts...'),
                    minimumInputLength: 1,
                    width: '100%',
                    ajax: {
                        url: ajax.customers,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term, page: params.page || 1 };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results || [],
                                pagination: data.pagination || {}
                            };
                        },
                        cache: true
                    },
                    templateResult: renderContactOption,
                    templateSelection: function (item) {
                        return item.name || item.text || (item.id ? 'Customer #' + item.id : '');
                    },
                    escapeMarkup: function (markup) { return markup; }
                });

                $contacts.on('change', function () {
                    var selected = $(this).val() || [];
                    customerIdsField.value(selected.join(','));
                });

                var initialIds = parseIds(customerIdsField.value());
                if (initialIds.length) {
                    console.log('WhatsApp Campaign JS: Pre-loading selected contacts: ' + initialIds.join(','));
                    $.ajax({
                        url: ajax.customers,
                        dataType: 'json',
                        data: { ids: initialIds.join(',') },
                        showLoader: true
                    }).done(function (resp) {
                        var results = (resp && resp.results) ? resp.results : [];
                        results.forEach(function (item) {
                            if (!item || !item.id) return;
                            var opt = new Option(item.text || item.name || ('Customer #' + item.id), String(item.id), true, true);
                            opt.dataset.name = item.name || '';
                            opt.dataset.email = item.email || '';
                            opt.dataset.phone = item.phone || '';
                            $contacts.append(opt);
                        });
                        $contacts.trigger('change');
                    });
                }
            });

            findComponent('variable_mapping', function (mappingField) {
                var mapping = safeParseJson(mappingField.value());

                $(document).on('change keyup', selectors.variablesContainer + ' .wa-variable-input', function () {
                    syncVariableMapping(mappingField);
                });

                findComponent('template_entity_id', function (templateField) {
                    function fetchAndRender(templateId) {
                        console.log('WhatsApp Campaign JS: Fetching variables for template ID: ' + templateId);
                        if (!templateId) {
                            $(selectors.variablesContainer).hide().empty();
                            $(selectors.headerPreview).hide().empty();
                            return;
                        }

                        $.ajax({
                            url: ajax.templateVars,
                            type: 'GET',
                            dataType: 'json',
                            data: { entity_id: templateId },
                            showLoader: true
                        }).done(function (resp) {
                            var vars = resp && resp.variables ? resp.variables : [];
                            renderVariables(vars, mapping);
                            renderHeaderPreview(resp.header_format, resp.header_image);
                            syncVariableMapping(mappingField);
                        });
                    }

                    fetchAndRender(templateField.value());
                    templateField.on('update', function (val) {
                        mapping = {};
                        mappingField.value('');
                        fetchAndRender(val);
                    });
                });
            });
        });
    };
});

