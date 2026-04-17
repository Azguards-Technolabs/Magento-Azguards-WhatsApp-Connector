<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class AddWhatsAppSyncAttributes implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CustomerSetupFactory $customerSetupFactory;
    private AttributeSetFactory $attributeSetFactory;

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
        $attributeSetId = (int)$customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = (int)$attributeSet->getDefaultGroupId($attributeSetId);

        // Add whatsapp_sync_status if missing (Boolean flag)
        if (!$customerSetup->getAttributeId(Customer::ENTITY, 'whatsapp_sync_status')) {
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
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
            ]);

            $syncStatusAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_sync_status');
            $syncStatusAttribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer']
            ]);
            $syncStatusAttribute->save();
        }

        // Add whatsapp_last_sync if missing (Timestamp)
        if (!$customerSetup->getAttributeId(Customer::ENTITY, 'whatsapp_last_sync')) {
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
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
            ]);

            $lastSyncAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_last_sync');
            $lastSyncAttribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => [],
                'visible' => false
            ]);
            $lastSyncAttribute->save();
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            AddWhatsAppPhoneAttribute::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
