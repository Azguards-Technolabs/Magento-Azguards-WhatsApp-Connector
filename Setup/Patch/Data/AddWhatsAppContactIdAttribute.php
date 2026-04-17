<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddWhatsAppContactIdAttribute implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CustomerSetupFactory $customerSetupFactory;
    private AttributeSetFactory $attributeSetFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = (int)$customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = (int)$attributeSet->getDefaultGroupId($attributeSetId);

        if (!$customerSetup->getAttributeId(Customer::ENTITY, 'whatsapp_contact_id')) {
            $customerSetup->addAttribute(Customer::ENTITY, 'whatsapp_contact_id', [
                'type' => 'varchar',
                'label' => 'WhatsApp Contact ID',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'user_defined' => true,
                'sort_order' => 120,
                'position' => 120,
                'system' => 0,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => true,
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_contact_id');
            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => [],
                'visible' => false,
            ]);
            $attribute->save();
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [
            AddWhatsAppSyncAttributes::class,
        ];
    }

    public function getAliases()
    {
        return [];
    }
}

