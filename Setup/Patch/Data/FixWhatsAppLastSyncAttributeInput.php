<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Fixes a production blocker:
 * If whatsapp_last_sync is created with frontend_input=datetime, Magento tries to instantiate
 * Magento\Eav\Model\Attribute\Data\Datetime (doesn't exist) during customer validation.
 */
class FixWhatsAppLastSyncAttributeInput implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CustomerSetupFactory $customerSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_last_sync');

        if (!$attribute || !$attribute->getId()) {
            return $this;
        }

        // Ensure a valid data model exists for validation.
        if ((string)$attribute->getFrontendInput() === 'datetime') {
            $attribute->setFrontendInput('date');
        }

        // Keep it non-editable in forms (this is maintained by sync jobs).
        $attribute->addData([
            'used_in_forms' => [],
            'visible' => false,
        ]);

        $attribute->save();
        return $this;
    }

    public static function getDependencies()
    {
        return [
            AddWhatsAppPhoneAttribute::class,
        ];
    }

    public function getAliases()
    {
        return [];
    }
}

