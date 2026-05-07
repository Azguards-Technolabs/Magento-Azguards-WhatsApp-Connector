<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewRegistration extends Preview
{
    /**
     * @param Context $context
     * @param VariableResolver $variableResolver
     * @param WhatsAppTemplateConfig $templateConfig
     * @param Json $json
     * @param TemplateCollectionFactory $templateCollectionFactory
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        VariableResolver $variableResolver,
        WhatsAppTemplateConfig $templateConfig,
        Json $json,
        TemplateCollectionFactory $templateCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $variableResolver, $templateConfig, $json, $templateCollectionFactory, $data);
    }

    /**
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getRegistrationTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'user_registration_template';
    }

    /**
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'customer_registration';
    }

    /**
     * @return array
     */
    public function getVariableGroups(): array
    {
        return [
            'customer' => [
                'label' => __('Customer'),
                'subgroups' => [
                    [
                        'label' => __('Required Attributes'),
                        'variables' => [
                            ['label' => __('First Name'), 'badge' => 'firstname', 'value' => '{{var customer.firstname}}'],
                            ['label' => __('Last Name'), 'badge' => 'lastname', 'value' => '{{var customer.lastname}}'],
                            ['label' => __('Email'), 'badge' => 'email', 'value' => '{{var customer.email}}'],
                        ]
                    ],
                    [
                        'label' => __('Basic Optional Attributes'),
                        'variables' => [
                            ['label' => __('Middle Name'), 'badge' => 'middlename', 'value' => '{{var customer.middlename}}'],
                            ['label' => __('Prefix'), 'badge' => 'prefix', 'value' => '{{var customer.prefix}}'],
                            ['label' => __('Suffix'), 'badge' => 'suffix', 'value' => '{{var customer.suffix}}'],
                            ['label' => __('Date of Birth'), 'badge' => 'dob', 'value' => '{{var customer.dob}}'],
                            ['label' => __('Gender'), 'badge' => 'gender', 'value' => '{{var customer.gender}}'],
                            ['label' => __('Tax/VAT Number'), 'badge' => 'taxvat', 'value' => '{{var customer.taxvat}}'],
                        ]
                    ],
                    [
                        'label' => __('Account / System Attributes'),
                        'variables' => [
                            ['label' => __('Entity ID'), 'badge' => 'entity_id', 'value' => '{{var customer.entity_id}}'],
                            ['label' => __('Website ID'), 'badge' => 'website_id', 'value' => '{{var customer.website_id}}'],
                            ['label' => __('Store ID'), 'badge' => 'store_id', 'value' => '{{var customer.store_id}}'],
                            ['label' => __('Created At'), 'badge' => 'created_at', 'value' => '{{var customer.created_at}}'],
                            ['label' => __('Updated At'), 'badge' => 'updated_at', 'value' => '{{var customer.updated_at}}'],
                            ['label' => __('Created In'), 'badge' => 'created_in', 'value' => '{{var customer.created_in}}'],
                            ['label' => __('Group ID'), 'badge' => 'group_id', 'value' => '{{var customer.group_id}}'],
                        ]
                    ]
                ]
            ],
            'address' => [
                'label' => __('Address'),
                'subgroups' => [
                    [
                        'label' => __('Address Attributes'),
                        'variables' => [
                            ['label' => __('Company'), 'badge' => 'company', 'value' => '{{var address.company}}'],
                            ['label' => __('Street'), 'badge' => 'street', 'value' => '{{var address.street}}'],
                            ['label' => __('City'), 'badge' => 'city', 'value' => '{{var address.city}}'],
                            ['label' => __('Region'), 'badge' => 'region', 'value' => '{{var address.region}}'],
                            ['label' => __('Region ID'), 'badge' => 'region_id', 'value' => '{{var address.region_id}}'],
                            ['label' => __('Postcode'), 'badge' => 'postcode', 'value' => '{{var address.postcode}}'],
                            ['label' => __('Country ID'), 'badge' => 'country_id', 'value' => '{{var address.country_id}}'],
                            ['label' => __('Telephone'), 'badge' => 'telephone', 'value' => '{{var address.telephone}}'],
                            ['label' => __('Fax'), 'badge' => 'fax', 'value' => '{{var address.fax}}'],
                            ['label' => __('VAT ID'), 'badge' => 'vat_id', 'value' => '{{var address.vat_id}}'],
                            ['label' => __('Is Default Billing'), 'badge' => 'is_default_billing', 'value' => '{{var address.is_default_billing}}'],
                            ['label' => __('Is Default Shipping'), 'badge' => 'is_default_shipping', 'value' => '{{var address.is_default_shipping}}'],
                        ]
                    ]
                ]
            ]
        ];
    }
}
