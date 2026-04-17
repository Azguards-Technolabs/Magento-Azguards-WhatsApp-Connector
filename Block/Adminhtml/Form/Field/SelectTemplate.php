<?php

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\UrlInterface;
use Magento\Backend\Block\Template\Context;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;

class SelectTemplate extends Field implements RendererInterface
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
    protected $requestType = 'default';

    // Static cache for options
    /**
     * @var TemplateCache
     */
    protected static $templateCache = [];

    /**
     * @var TemplateCollectionFactory
     */
    protected $templateCollectionFactory;

    /**
     * SelectTemplate construct
     *
     * @param Context $context
     * @param ApiHelper $apiHelper
     * @param UrlInterface $urlInterface
     * @param TemplateCollectionFactory $templateCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ApiHelper $apiHelper,
        UrlInterface $urlInterface,
        TemplateCollectionFactory $templateCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiHelper = $apiHelper;
        $this->urlInterface = $urlInterface;
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * Get request type
     *
     * @return string
     */
    protected function getRequestType()
    {
        return $this->requestType;
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
        $fieldConfig = $element->getFieldConfig();
        $conectorConfigPath = $fieldConfig['path'] ?? ''; // whatsApp_conector/user_registration

        $conectorConfigPart = explode('/', $conectorConfigPath);
        $groupId = $conectorConfigPart[1] ?? 'default';
        $requestType = str_replace('_', '', $groupId);
        $requestUrlFiled = trim($requestType, '"');
        if ($requestUrlFiled == 'ordercreation') {
            $requestUrlFiled = 'ordercreate';
        }
        $cssClass = 'searchable-dropdown-' . $requestUrlFiled;

        $html = '<label for="' . $id . '"><strong>Select Template</strong></label>';
        $html .= '<select id="' . $id . '" name="' . $name . '" class="admin__control-select ' . $cssClass . '">';
        $html .= '<option value="">Select Template</option>';

        foreach ($this->getOptions() as $value => $label) {
            $selected = ($selectedValue == $value) ? 'selected="selected"' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }

        $html .= '</select>';
        // Init Select2 + AJAX script (CSS loaded from module to avoid external CDN dependency)
        $html .= '<script>
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
                                url: "' . $this->getAjaxUrl($requestUrlFiled) . '",
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
     * Get AJAX URL for template data
     *
     * @param string $requestUrlFiled
     * @return string
     */
    protected function getAjaxUrl($requestUrlFiled)
    {
        return $this->urlInterface->getUrl('whatsappconnect/template/'.$requestUrlFiled);
    }

    /**
     * Get template options from cache or API
     *
     * @return array
     */
    protected function getOptions()
    {
        $cacheKey = $this->requestType;
        // Check static cache
        if (isset(self::$templateCache[$cacheKey])) {
            return self::$templateCache[$cacheKey];
        }

        $options = [];
        // Fetch template list from local database instead of API
        $collection = $this->templateCollectionFactory->create();
        $collection->addFieldToFilter('status', 'APPROVED');

        foreach ($collection as $template) {
            $options[$template->getTemplateId()] = $template->getTemplateName();
        }

        // Save in static cache
        self::$templateCache[$cacheKey] = $options;

        return $options;
    }
}
