<?php

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\UrlInterface;

class TemplateDropdown extends Field implements RendererInterface
{
    /**
     * @var ApiHelper
     */
    protected $apiHelper;
    /**
     * @var UrlInterface
     */
    protected $urlInterface;
    /**
     * @var RequestType
     */
    protected $requestType;

    // Static cache for options
    /**
     * @var TemplateCache
     */
    protected static $templateCache = [];

    /**
     * TemplateDropdown construct
     *
     * @param ApiHelper $apiHelper
     * @param UrlInterface $urlInterface
     * @param array $data
     */
    public function __construct(
        ApiHelper $apiHelper,
        UrlInterface $urlInterface,
        array $data = []
    ) {
        $this->apiHelper = $apiHelper;
        $this->urlInterface = $urlInterface;
        $this->requestType = $data['request_type'] ?? 'default';
    }

    /**
     * Execute to render template variables HTML block
     *
     * @param AbstractElement $element
     * @return void
     */
    public function render(AbstractElement $element)
    {
        $id = $element->getHtmlId();
        $name = $element->getName();
        $selectedValue = $element->getValue();
        $cssClass = 'searchable-dropdown-' . $this->requestType;

        $html = '<label for="' . $id . '"><strong>Select Template</strong></label>';
        $html .= '<select id="' . $id . '" name="' . $name . '" class="admin__control-select ' . $cssClass . '">';
        $html .= '<option value="">Select Template</option>';

        foreach ($this->getOptions() as $value => $label) {
            $selected = ($selectedValue == $value) ? 'selected="selected"' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }

        $html .= '</select>';

        // Include Select2 and JS
        $html .= '<link rel="stylesheet" 
        href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
        <script>
            require(["jquery", "select2"], function($) {
                $(document).ready(function() {
                    var dropdown = $(".' . $cssClass . '");

                    dropdown.select2({
                        placeholder: "Select...",
                        allowClear: true,
                        width: "100%"
                    });

                    dropdown.on("change", function(e) {
                        e.preventDefault();
                        var selectedValue = $(this).val();
                        var selectedId = $(this).closest(".config.admin__collapsible-block").attr("id");

                        if (selectedValue) {
                            jQuery.ajax({
                                url: "' . $this->getAjaxUrl() . '",
                                type: "POST",
                                data: {
                                    template_id: selectedValue,
                                    field_id: selectedId,
                                    requestUrl: "' . $this->requestType . '"
                                },
                                showLoader: true,
                                success: function(response) {
                                    var targetElement = jQuery("#" + response.id).find("#searchautocomplete-indices");
                                    if (targetElement.length > 0) {
                                        targetElement.html(response.data);
                                    } else {
                                        $("#" + selectedId).append(response.data);
                                    }
                                },
                                error: function(error) {
                                    console.log("AJAX Error:", error);
                                }
                            });
                        }
                    });
                });
            });
        </script>';

        return $html;
    }

    /**
     * Get Ajax Url
     *
     * @return void
     */
    protected function getAjaxUrl()
    {
        return $this->urlInterface->getUrl('whatsappconnect/template/' . $this->requestType);
    }

    /**
     * Get Options
     *
     * @return void
     */
    protected function getOptions()
    {
        $cacheKey = $this->requestType;

        // Check static cache
        if (isset(self::$templateCache[$cacheKey])) {
            return self::$templateCache[$cacheKey];
        }

        // Fetch from API
        $response = $this->apiHelper->fetchTemplates();
        $options = [];

        if (!empty($response["Result"]["data"])) {
            foreach ($response["Result"]["data"] as $item) {
                if (isset($item["id"], $item["templateName"])) {
                    $options[$item["id"]] = $item["id"] . ' -- ' . $item["templateName"];
                }
            }
        }

        // Save in static cache
        self::$templateCache[$cacheKey] = $options;

        return $options;
    }
}
