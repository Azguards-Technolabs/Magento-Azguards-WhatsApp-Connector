<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;

class Preview extends Field
{
    /**
     * @var VariableResolver
     */
    private VariableResolver $variableResolver;

    /**
     * @var WhatsAppTemplateConfig
     */
    private WhatsAppTemplateConfig $templateConfig;

    /**
     * @var Json
     */
    private Json $json;

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
        parent::__construct($context, $data);
        $this->variableResolver = $variableResolver;
        $this->templateConfig = $templateConfig;
        $this->json = $json;
        $this->setTemplate('Azguards_WhatsAppConnect::system/config/field/preview.phtml');
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->setData('element', $element);

        return $this->_toHtml();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSampleData(): array
    {
        return $this->variableResolver->getSampleData();
    }

    /**
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);

        return $this->templateConfig->getOrderTemplateConfig($storeId ?: null);
    }

    /**
     * @return array<string, string>
     */
    public function getInitialPreview(): array
    {
        $config = $this->getInitialConfig();
        $sampleData = $this->getSampleData();
        $bodyTemplate = (string)($config['body_template'] ?: 'Hi {{customer_firstname}}, your order {{increment_id}} total is {{grand_total}}.');
        $footerTemplate = (string)($config['footer_template'] ?: 'Thank you for shopping with us.');

        $header = '';
        if (($config['header_type'] ?? 'none') === 'text') {
            $header = $this->variableResolver->resolveWithData((string)($config['header_text'] ?? ''), $sampleData);
        }

        return [
            'header' => $header,
            'body' => $this->variableResolver->resolveWithData($bodyTemplate, $sampleData),
            'footer' => $this->variableResolver->resolveWithData($footerTemplate, $sampleData),
            'buttons' => [],
        ];
    }

    /**
     * @return string
     */
    public function getBuilderConfigJson(): string
    {
        return $this->json->serialize([
            'selectors' => [
                'headerType' => '#whatsapp_template_order_template_header_type',
                'headerText' => '#whatsapp_template_order_template_header_text',
                'bodyTemplate' => '#whatsapp_template_order_template_body_template',
                'footerTemplate' => '#whatsapp_template_order_template_footer_template',
                'templateName' => '#whatsapp_template_order_template_template_name',
                'category' => '#whatsapp_template_order_template_category',
                'language' => '#whatsapp_template_order_template_language',
                'headerHandle' => '#whatsapp_template_order_template_header_handle',
                'headerImage' => '#whatsapp_template_order_template_header_image',
                'buttonsJson' => '#whatsapp_template_order_template_buttons_json',
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
        ]);
    }
}
