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
    protected VariableResolver $variableResolver;

    /**
     * @var WhatsAppTemplateConfig
     */
    protected WhatsAppTemplateConfig $templateConfig;

    /**
     * @var Json
     */
    protected Json $json;

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
        $elementId = $this->getData('element')->getHtmlId();
        $groupName = $this->getGroupName();

        return $this->json->serialize([
            'selectors' => [
                'headerType' => '#whatsapp_template_' . $groupName . '_header_type',
                'headerText' => '#whatsapp_template_' . $groupName . '_header_text',
                'bodyTemplate' => '#whatsapp_template_' . $groupName . '_body_template',
                'footerTemplate' => '#whatsapp_template_' . $groupName . '_footer_template',
                'templateName' => '#whatsapp_template_' . $groupName . '_template_name',
                'category' => '#whatsapp_template_' . $groupName . '_category',
                'language' => '#whatsapp_template_' . $groupName . '_language',
                'headerHandle' => '#whatsapp_template_' . $groupName . '_header_handle',
                'headerImage' => '#whatsapp_template_' . $groupName . '_header_image',
                'buttonsJson' => '#whatsapp_template_' . $groupName . '_buttons_json',
                'eventCodeInput' => '#whatsapp_template_' . $groupName . '_event_code',
                'builderTemplateName' => '#' . $elementId . '-builder-template-name',
                'builderCategory' => '#' . $elementId . '-builder-category',
                'builderLanguage' => '#' . $elementId . '-builder-language',
                'builderHeaderType' => '#' . $elementId . '-builder-header-type',
                'builderHeaderText' => '#' . $elementId . '-builder-header-text',
                'builderBody' => '#' . $elementId . '-builder-body',
                'builderFooter' => '#' . $elementId . '-builder-footer',
                'builderVariableSelect' => '#' . $elementId . '-variable-select',
                'previewHeader' => '[data-role="wa-preview-header"]',
                'previewMedia' => '[data-role="wa-preview-media"]',
                'previewBody' => '[data-role="wa-preview-body"]',
                'previewFooter' => '[data-role="wa-preview-footer"]',
                'previewButtons' => '[data-role="wa-preview-buttons"]',
                'mediaUploadInput' => '.wa-header-media-file',
                'mediaUploadButton' => '.wa-header-media-upload',
                'mediaUploadStatus' => '.wa-header-media-status',
                'mediaPreview' => '[data-role="wa-header-media-preview"]',
                'mediaSection' => '.wa-builder-header-media-section',
                'headerTextSection' => '.wa-builder-header-text-section',
                'addButtonRow' => '.wa-add-button-row',
                'buttonsRows' => '.wa-buttons-rows',
                'saveTemplateButton' => '.wa-save-template',
                'saveTemplateStatus' => '.wa-save-template-status',
            ],
            'sampleData' => $this->getSampleData(),
            'uploadUrl' => $this->getUrl('whatsappconnect/config/upload'),
            'saveTemplateUrl' => $this->getUrl('whatsappconnect/config/createTemplate'),
            'storeId' => (int)$this->getRequest()->getParam('store', 0),
            'eventCode' => $this->getEventCode(),
        ]);
    }

    /**
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_template';
    }

    /**
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_created';
    }

    /**
     * Return event-specific variables grouped for the UI tabs.
     *
     * @return array
     */
    public function getVariableGroups(): array
    {
        return [
            'order' => [
                'label' => __('Order'),
                'subgroups' => [
                    [
                        'label' => __('Order Details'),
                        'variables' => [
                            ['label' => __('Order ID'), 'badge' => 'increment_id', 'value' => '{{var order.increment_id}}'],
                            ['label' => __('Entity ID'), 'badge' => 'entity_id', 'value' => '{{var order.entity_id}}'],
                            ['label' => __('Status'), 'badge' => 'status', 'value' => '{{var order.status}}'],
                            ['label' => __('State'), 'badge' => 'state', 'value' => '{{var order.state}}'],
                            ['label' => __('Created At'), 'badge' => 'created_at', 'value' => '{{var order.created_at}}'],
                        ]
                    ],
                    [
                        'label' => __('Order Totals'),
                        'variables' => [
                            ['label' => __('Grand Total'), 'badge' => 'grand_total', 'value' => '{{var order.grand_total}}'],
                            ['label' => __('Subtotal'), 'badge' => 'subtotal', 'value' => '{{var order.subtotal}}'],
                            ['label' => __('Shipping Amount'), 'badge' => 'shipping_amount', 'value' => '{{var order.shipping_amount}}'],
                            ['label' => __('Tax Amount'), 'badge' => 'tax_amount', 'value' => '{{var order.tax_amount}}'],
                            ['label' => __('Discount'), 'badge' => 'discount_amount', 'value' => '{{var order.discount_amount}}'],
                            ['label' => __('Item Count'), 'badge' => 'total_item_count', 'value' => '{{var order.total_item_count}}'],
                        ]
                    ]
                ]
            ],
            'customer' => [
                'label' => __('Customer'),
                'subgroups' => [
                    [
                        'label' => __('Customer Information'),
                        'variables' => [
                            ['label' => __('First Name'), 'badge' => 'firstname', 'value' => '{{var order.customer_firstname}}'],
                            ['label' => __('Last Name'), 'badge' => 'lastname', 'value' => '{{var order.customer_lastname}}'],
                            ['label' => __('Email'), 'badge' => 'email', 'value' => '{{var order.customer_email}}'],
                            ['label' => __('Customer ID'), 'badge' => 'id', 'value' => '{{var order.customer_id}}'],
                            ['label' => __('Group ID'), 'badge' => 'group_id', 'value' => '{{var order.customer_group_id}}'],
                        ]
                    ]
                ]
            ],
            'address' => [
                'label' => __('Address'),
                'subgroups' => [
                    [
                        'label' => __('Billing Address'),
                        'variables' => [
                            ['label' => __('First Name'), 'badge' => 'firstname', 'value' => '{{var order.getBillingAddress().getFirstname()}}'],
                            ['label' => __('Last Name'), 'badge' => 'lastname', 'value' => '{{var order.getBillingAddress().getLastname()}}'],
                            ['label' => __('Street Line 1'), 'badge' => 'street', 'value' => '{{var order.getBillingAddress().getStreetLine(1)}}'],
                            ['label' => __('City'), 'badge' => 'city', 'value' => '{{var order.getBillingAddress().getCity()}}'],
                            ['label' => __('Region'), 'badge' => 'region', 'value' => '{{var order.getBillingAddress().getRegion()}}'],
                            ['label' => __('Postcode'), 'badge' => 'postcode', 'value' => '{{var order.getBillingAddress().getPostcode()}}'],
                            ['label' => __('Country'), 'badge' => 'country_id', 'value' => '{{var order.getBillingAddress().getCountryId()}}'],
                            ['label' => __('Telephone'), 'badge' => 'telephone', 'value' => '{{var order.getBillingAddress().getTelephone()}}'],
                        ]
                    ],
                    [
                        'label' => __('Shipping Address'),
                        'variables' => [
                            ['label' => __('First Name'), 'badge' => 'firstname', 'value' => '{{var order.getShippingAddress().getFirstname()}}'],
                            ['label' => __('Last Name'), 'badge' => 'lastname', 'value' => '{{var order.getShippingAddress().getLastname()}}'],
                            ['label' => __('Street Line 1'), 'badge' => 'street', 'value' => '{{var order.getShippingAddress().getStreetLine(1)}}'],
                            ['label' => __('City'), 'badge' => 'city', 'value' => '{{var order.getShippingAddress().getCity()}}'],
                            ['label' => __('Region'), 'badge' => 'region', 'value' => '{{var order.getShippingAddress().getRegion()}}'],
                            ['label' => __('Postcode'), 'badge' => 'postcode', 'value' => '{{var order.getShippingAddress().getPostcode()}}'],
                            ['label' => __('Country'), 'badge' => 'country_id', 'value' => '{{var order.getShippingAddress().getCountryId()}}'],
                            ['label' => __('Telephone'), 'badge' => 'telephone', 'value' => '{{var order.getShippingAddress().getTelephone()}}'],
                        ]
                    ]
                ]
            ],
            'items' => [
                'label' => __('Items'),
                'subgroups' => [
                    [
                        'label' => __('Order Items Loop'),
                        'variables' => [
                            [
                                'label' => __('Basic Loop Syntax'),
                                'badge' => 'items',
                                'value' => '{{#items}}{{var items.name}} x {{var items.qty_ordered}} = {{var items.row_total}}{{/items}}',
                                'style' => 'background: #fff0eb; border-color: #ffd6cc;',
                                'label_style' => 'font-weight:600; color:#d63c19;',
                                'badge_style' => 'background:#ffdbd1; color:#c22e0e;'
                            ],
                            ['label' => __('Product Name'), 'badge' => 'name', 'value' => '{{var items.name}}'],
                            ['label' => __('SKU'), 'badge' => 'sku', 'value' => '{{var items.sku}}'],
                            ['label' => __('Qty Ordered'), 'badge' => 'qty_ordered', 'value' => '{{var items.qty_ordered}}'],
                            ['label' => __('Price'), 'badge' => 'price', 'value' => '{{var items.price}}'],
                            ['label' => __('Row Total'), 'badge' => 'row_total', 'value' => '{{var items.row_total}}'],
                            ['label' => __('Product ID'), 'badge' => 'product_id', 'value' => '{{var items.product_id}}'],
                        ]
                    ]
                ]
            ],
            'payment' => [
                'label' => __('Payment'),
                'subgroups' => [
                    [
                        'label' => __('Payment Info'),
                        'variables' => [
                            ['label' => __('Payment Method'), 'badge' => 'method', 'value' => '{{var order.getPayment().getMethodInstance().getTitle()}}'],
                            ['label' => __('Shipping Method'), 'badge' => 'shipping_description', 'value' => '{{var order.getShippingDescription()}}'],
                            ['label' => __('Coupon Code'), 'badge' => 'coupon_code', 'value' => '{{var order.getCouponCode()}}'],
                            ['label' => __('Currency'), 'badge' => 'currency', 'value' => '{{var order.getOrderCurrencyCode()}}'],
                        ]
                    ]
                ]
            ]
        ];
    }
}
