<?php
namespace Azguards\WhatsAppConnect\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\View\Element\Html\Select;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class OrderCreditMemo extends Field implements RendererInterface
{

    protected $apiHelper;
    protected $urlInterface;

    public function __construct(
        ApiHelper $apiHelper,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
         $this->urlInterface = $urlInterface;
        $this->apiHelper = $apiHelper;
    }


    public function render(AbstractElement $element)
    {
        $html = '<label for="' . $element->getHtmlId() . '"><strong>Select Template</strong></label>';
        $html .= '<select id="' . $element->getHtmlId() . '" name="' . $element->getName() . '" class="admin__control-select searchable-dropdown-creditMemo">';

        $options = $this->getOptions();
        $selectedValue = $element->getValue(); // Get the current value

        // Add a default option
        $html .= '<option value="">Select Template</option>';

        foreach ($options as $value => $label) {
            $selected = ($selectedValue == $value) ? 'selected="selected"' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }

        $html .= '</select>';

        // Init Select2 + AJAX script (CSS loaded from module to avoid external CDN dependency)
        $html .= '<script>
            require(["jquery", "select2"], function($) {
                $(document).ready(function() {
                    var dropdown = $(".searchable-dropdown-creditMemo");

                    dropdown.select2({
                        placeholder: "Select...",
                        allowClear: true,
                        width: "100%"
                    });

                    dropdown.on("change", function(e) {
                         e.preventDefault();
                        var selectedValue = $(this).val();
                        var selectedId = $(this).closest(".config.admin__collapsible-block").attr("id");;
                        if (selectedValue) {
                            jQuery.ajax({
                                url: "' . $this->getAjaxUrl() . '", // AJAX URL
                                type: "POST",
                                data: { template_id: selectedValue, field_id: selectedId, requesrUrl: "orderInvoice" },
                                showLoader: true,
                                success: function(response) {
                                    var targetElement = $("#searchautocomplete-indices");
                                    var targetElement = jQuery("#" + response.id).find("#searchautocomplete-indices");
                                    if (targetElement.length > 0) {
                                        // If the element exists, replace its content
                                        targetElement.html(response.data);
                                    } else {
                                        $("#" + selectedId).append(response.data);
                                    }
                                    console.log("AJAX Success:", response.data);
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

    public function getAjaxUrl()
    {
        return $this->urlInterface->getUrl('whatsappconnect/template/ordercreditmemo');
    }

    public function getOptions()
    {
        $response = $this->apiHelper->fetchTemplates();
        $options = [];
        if (!empty($response["result"]["data"])) {
            foreach ($response["result"]["data"] as $item) {
                if (isset($item["id"], $item["templateName"])) {
                    $options[$item["id"]] = $item["id"] . '--' . $item["templateName"];
                }
            }
        }
        return $options;
    }
}
