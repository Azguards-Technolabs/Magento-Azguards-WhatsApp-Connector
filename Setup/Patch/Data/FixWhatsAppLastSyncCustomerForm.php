<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixWhatsAppLastSyncCustomerForm implements DataPatchInterface
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
        $lastSyncAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'whatsapp_last_sync');

        if (!$lastSyncAttribute || !$lastSyncAttribute->getId()) {
            return $this;
        }

        // This value is maintained by sync jobs, so it should not be rendered in the admin customer form.
        $lastSyncAttribute->addData([
            'used_in_forms' => [],
            'visible' => false,
        ]);
        $lastSyncAttribute->save();

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
