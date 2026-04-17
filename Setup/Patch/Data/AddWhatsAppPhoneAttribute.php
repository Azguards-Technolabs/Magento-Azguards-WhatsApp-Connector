<?php

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class AddWhatsAppPhoneAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, 'whatsapp_phone_number', [
            'type' => 'varchar',
            'label' => 'WhatsApp Phone Number',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'sort_order' => 100,
            'position' => 100,
            'system' => 0,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
            'is_searchable_in_grid' => true,
        ]);

        $customerSetup->addAttribute(Customer::ENTITY, 'whatsapp_country_code', [
            'type' => 'varchar',
            'label' => 'WhatsApp Country Code',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'sort_order' => 105,
            'position' => 105,
            'system' => 0,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
        ]);

        $customerSetup->addAttribute(Customer::ENTITY, 'whatsapp_sync_status', [
            'type' => 'int',
            'label' => 'WhatsApp Sync Status',
            'input' => 'select',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'default' => 0,
            'sort_order' => 110,
            'position' => 110,
            'system' => 0,
        ]);

        $customerSetup->addAttribute(Customer::ENTITY, 'whatsapp_last_sync', [
            'type' => 'datetime',
            'label' => 'WhatsApp Last Sync',
            // Magento EAV does not have Attribute\Data\Datetime; use date input for datetime backend_type.
            'input' => 'date',
            'required' => false,
            'visible' => false,
            'user_defined' => true,
            'sort_order' => 115,
            'position' => 115,
            'system' => 0,
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_phone_number');

        $attribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer',
                'customer_account_create',
                'customer_account_edit'
            ]
        ]);

        $attribute->save();

        $countryCodeAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_country_code');
        $countryCodeAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer',
                'customer_account_create',
                'customer_account_edit'
            ]
        ]);
        $countryCodeAttribute->save();

        $syncStatusAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_sync_status');
        $syncStatusAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer'
            ]
        ]);
        $syncStatusAttribute->save();

        $lastSyncAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_last_sync');
        $lastSyncAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => []
        ]);
        $lastSyncAttribute->save();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
