<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewAbandonedCart extends Preview
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
        $config = $this->templateConfig->getAbandonedCartTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'abandoned_cart_template';
    }

    /**
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'abandon_cart';
    }

    /**
     * @return array
     */
    public function getVariableGroups(): array
    {
        return [
            'quote' => [
                'label' => __('Cart / Quote'),
                'subgroups' => [
                    [
                        'label' => __('Quote Main Entity'),
                        'variables' => [
                            ['label' => __('Entity ID'), 'badge' => 'entity_id', 'value' => '{{var quote.entity_id}}'],
                            ['label' => __('Store ID'), 'badge' => 'store_id', 'value' => '{{var quote.store_id}}'],
                            ['label' => __('Website ID'), 'badge' => 'website_id', 'value' => '{{var quote.website_id}}'],
                            ['label' => __('Customer ID'), 'badge' => 'customer_id', 'value' => '{{var quote.customer_id}}'],
                            ['label' => __('Customer Email'), 'badge' => 'customer_email', 'value' => '{{var quote.customer_email}}'],
                            ['label' => __('First Name'), 'badge' => 'customer_firstname', 'value' => '{{var quote.customer_firstname}}'],
                            ['label' => __('Last Name'), 'badge' => 'customer_lastname', 'value' => '{{var quote.customer_lastname}}'],
                            ['label' => __('Group ID'), 'badge' => 'customer_group_id', 'value' => '{{var quote.customer_group_id}}'],
                            ['label' => __('Is Active'), 'badge' => 'is_active', 'value' => '{{var quote.is_active}}'],
                            ['label' => __('Items Count'), 'badge' => 'items_count', 'value' => '{{var quote.items_count}}'],
                            ['label' => __('Items Quantity'), 'badge' => 'items_qty', 'value' => '{{var quote.items_qty}}'],
                            ['label' => __('Created At'), 'badge' => 'created_at', 'value' => '{{var quote.created_at}}'],
                            ['label' => __('Updated At'), 'badge' => 'updated_at', 'value' => '{{var quote.updated_at}}'],
                            ['label' => __('Converted At'), 'badge' => 'converted_at', 'value' => '{{var quote.converted_at}}'],
                            ['label' => __('Reserved Order ID'), 'badge' => 'reserved_order_id', 'value' => '{{var quote.reserved_order_id}}'],
                        ]
                    ],
                    [
                        'label' => __('Cart Totals'),
                        'variables' => [
                            ['label' => __('Subtotal'), 'badge' => 'subtotal', 'value' => '{{var quote.subtotal}}'],
                            ['label' => __('Base Subtotal'), 'badge' => 'base_subtotal', 'value' => '{{var quote.base_subtotal}}'],
                            ['label' => __('Grand Total'), 'badge' => 'grand_total', 'value' => '{{var quote.grand_total}}'],
                            ['label' => __('Base Grand Total'), 'badge' => 'base_grand_total', 'value' => '{{var quote.base_grand_total}}'],
                            ['label' => __('Discount Amount'), 'badge' => 'discount_amount', 'value' => '{{var quote.discount_amount}}'],
                            ['label' => __('Base Discount Amount'), 'badge' => 'base_discount_amount', 'value' => '{{var quote.base_discount_amount}}'],
                            ['label' => __('Shipping Amount'), 'badge' => 'shipping_amount', 'value' => '{{var quote.shipping_amount}}'],
                            ['label' => __('Base Shipping Amount'), 'badge' => 'base_shipping_amount', 'value' => '{{var quote.base_shipping_amount}}'],
                            ['label' => __('Tax Amount'), 'badge' => 'tax_amount', 'value' => '{{var quote.tax_amount}}'],
                            ['label' => __('Base Tax Amount'), 'badge' => 'base_tax_amount', 'value' => '{{var quote.base_tax_amount}}'],
                            ['label' => __('Subtotal With Discount'), 'badge' => 'subtotal_with_discount', 'value' => '{{var quote.subtotal_with_discount}}'],
                        ]
                    ]
                ]
            ],
            'items' => [
                'label' => __('Items'),
                'subgroups' => [
                    [
                        'label' => __('Quote Items Loop'),
                        'variables' => [
                            [
                                'label' => __('Basic Loop Syntax'),
                                'badge' => 'items',
                                'value' => '{{#items}}{{var items.name}} x {{var items.qty}} = {{var items.row_total}}{{/items}}',
                                'style' => 'background: #fff0eb; border-color: #ffd6cc;',
                                'label_style' => 'font-weight:600; color:#d63c19;',
                                'badge_style' => 'background:#ffdbd1; color:#c22e0e;'
                            ],
                            ['label' => __('Item ID'), 'badge' => 'item_id', 'value' => '{{var items.item_id}}'],
                            ['label' => __('Product ID'), 'badge' => 'product_id', 'value' => '{{var items.product_id}}'],
                            ['label' => __('SKU'), 'badge' => 'sku', 'value' => '{{var items.sku}}'],
                            ['label' => __('Product Name'), 'badge' => 'name', 'value' => '{{var items.name}}'],
                            ['label' => __('Price'), 'badge' => 'price', 'value' => '{{var items.price}}'],
                            ['label' => __('Quantity'), 'badge' => 'qty', 'value' => '{{var items.qty}}'],
                            ['label' => __('Row Total'), 'badge' => 'row_total', 'value' => '{{var items.row_total}}'],
                            ['label' => __('Product Type'), 'badge' => 'product_type', 'value' => '{{var items.product_type}}'],
                            ['label' => __('Weight'), 'badge' => 'weight', 'value' => '{{var items.weight}}'],
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
                            ['label' => __('First Name'), 'badge' => 'firstname', 'value' => '{{var billing.firstname}}'],
                            ['label' => __('Last Name'), 'badge' => 'lastname', 'value' => '{{var billing.lastname}}'],
                            ['label' => __('Street'), 'badge' => 'street', 'value' => '{{var billing.street}}'],
                            ['label' => __('City'), 'badge' => 'city', 'value' => '{{var billing.city}}'],
                            ['label' => __('Region'), 'badge' => 'region', 'value' => '{{var billing.region}}'],
                            ['label' => __('Postcode'), 'badge' => 'postcode', 'value' => '{{var billing.postcode}}'],
                            ['label' => __('Country ID'), 'badge' => 'country_id', 'value' => '{{var billing.country_id}}'],
                            ['label' => __('Telephone'), 'badge' => 'telephone', 'value' => '{{var billing.telephone}}'],
                            ['label' => __('Company'), 'badge' => 'company', 'value' => '{{var billing.company}}'],
                            ['label' => __('Email'), 'badge' => 'email', 'value' => '{{var billing.email}}'],
                        ]
                    ],
                    [
                        'label' => __('Shipping Address'),
                        'variables' => [
                            ['label' => __('First Name'), 'badge' => 'firstname', 'value' => '{{var shipping.firstname}}'],
                            ['label' => __('Last Name'), 'badge' => 'lastname', 'value' => '{{var shipping.lastname}}'],
                            ['label' => __('Street'), 'badge' => 'street', 'value' => '{{var shipping.street}}'],
                            ['label' => __('City'), 'badge' => 'city', 'value' => '{{var shipping.city}}'],
                            ['label' => __('Region'), 'badge' => 'region', 'value' => '{{var shipping.region}}'],
                            ['label' => __('Postcode'), 'badge' => 'postcode', 'value' => '{{var shipping.postcode}}'],
                            ['label' => __('Country ID'), 'badge' => 'country_id', 'value' => '{{var shipping.country_id}}'],
                            ['label' => __('Telephone'), 'badge' => 'telephone', 'value' => '{{var shipping.telephone}}'],
                            ['label' => __('Company'), 'badge' => 'company', 'value' => '{{var shipping.company}}'],
                        ]
                    ]
                ]
            ],
            'other' => [
                'label' => __('Other'),
                'subgroups' => [
                    [
                        'label' => __('Shipping Method'),
                        'variables' => [
                            ['label' => __('Method'), 'badge' => 'shipping_method', 'value' => '{{var quote.shipping_method}}'],
                            ['label' => __('Description'), 'badge' => 'shipping_description', 'value' => '{{var quote.shipping_description}}'],
                        ]
                    ],
                    [
                        'label' => __('Coupon / Discount'),
                        'variables' => [
                            ['label' => __('Coupon Code'), 'badge' => 'coupon_code', 'value' => '{{var quote.coupon_code}}'],
                            ['label' => __('Discount Amount'), 'badge' => 'discount_amount', 'value' => '{{var quote.discount_amount}}'],
                        ]
                    ],
                    [
                        'label' => __('Currency'),
                        'variables' => [
                            ['label' => __('Quote Currency'), 'badge' => 'quote_currency_code', 'value' => '{{var quote.quote_currency_code}}'],
                            ['label' => __('Base Currency'), 'badge' => 'base_currency_code', 'value' => '{{var quote.base_currency_code}}'],
                            ['label' => __('Store Currency'), 'badge' => 'store_currency_code', 'value' => '{{var quote.store_currency_code}}'],
                        ]
                    ]
                ]
            ]
        ];
    }
}
