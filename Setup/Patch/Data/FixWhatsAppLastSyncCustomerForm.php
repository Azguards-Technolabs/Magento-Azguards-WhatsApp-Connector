<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixWhatsAppLastSyncCustomerForm implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private CustomerSetupFactory $customerSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * Remove the last sync attribute from admin customer forms.
     *
     * @return self
     */
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

    /**
     * Return data patch dependencies.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [
            AddWhatsAppSyncAttributes::class,
        ];
    }

    /**
     * Return data patch aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
