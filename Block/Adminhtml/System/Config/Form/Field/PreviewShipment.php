<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewShipment extends Preview
{
    /**
     * @param Context $context
     * @param VariableResolver $variableResolver
     * @param WhatsAppTemplateConfig $templateConfig
     * @param Json $json
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        VariableResolver $variableResolver,
        WhatsAppTemplateConfig $templateConfig,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $variableResolver, $templateConfig, $json, $data);
    }

    /**
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->templateConfig->getShipmentTemplateConfig($storeId ?: null);
    }

    /**
     * @return string
     */
    public function getBuilderConfigJson(): string
    {
        return $this->json->serialize([
            'selectors' => [
                'headerType' => '#whatsapp_template_order_shipment_template_header_type',
                'headerText' => '#whatsapp_template_order_shipment_template_header_text',
                'bodyTemplate' => '#whatsapp_template_order_shipment_template_body_template',
                'footerTemplate' => '#whatsapp_template_order_shipment_template_footer_template',
                'templateName' => '#whatsapp_template_order_shipment_template_template_name',
                'category' => '#whatsapp_template_order_shipment_template_category',
                'language' => '#whatsapp_template_order_shipment_template_language',
                'headerHandle' => '#whatsapp_template_order_shipment_template_header_handle',
                'headerImage' => '#whatsapp_template_order_shipment_template_header_image',
                'buttonsJson' => '#whatsapp_template_order_shipment_template_buttons_json',
                'eventCodeInput' => '#whatsapp_template_order_shipment_template_event_code',
                'builderTemplateName' => '#wa-builder-template-name',
                'builderCategory' => '#wa-builder-category',
                'builderLanguage' => '#wa-builder-language',
                'builderHeaderType' => '#wa-builder-header-type',
                'builderHeaderText' => '#wa-builder-header-text',
                'builderBody' => '#wa-builder-body',
                'builderFooter' => '#wa-builder-footer',
                'builderVariableSelect' => '#wa-variable-select',
                'previewHeader' => '[data-role="wa-preview-header"]',
                'previewMedia' => '[data-role="wa-preview-media"]',
                'previewBody' => '[data-role="wa-preview-body"]',
                'previewFooter' => '[data-role="wa-preview-footer"]',
                'previewButtons' => '[data-role="wa-preview-buttons"]',
                'mediaUploadInput' => '#wa-header-media-file',
                'mediaUploadButton' => '#wa-header-media-upload',
                'mediaUploadStatus' => '#wa-header-media-status',
                'mediaPreview' => '[data-role="wa-header-media-preview"]',
                'mediaSection' => '#wa-builder-header-media-section',
                'headerTextSection' => '#wa-builder-header-text-section',
                'addButtonRow' => '#wa-add-button-row',
                'buttonsRows' => '#wa-buttons-rows',
                'saveTemplateButton' => '#wa-save-template',
                'saveTemplateStatus' => '#wa-save-template-status',
            ],
            'sampleData' => $this->getSampleData(),
            'uploadUrl' => $this->getUrl('whatsappconnect/config/upload'),
            'saveTemplateUrl' => $this->getUrl('whatsappconnect/config/createTemplate'),
            'storeId' => (int)$this->getRequest()->getParam('store', 0),
            'eventCode' => 'order_shipment',
        ]);
    }
}