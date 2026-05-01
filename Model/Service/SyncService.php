<?php

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;

class SyncService
{
    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerDataBuilder
     */
    private CustomerDataBuilder $dataBuilder;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @param ApiHelper $apiHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerDataBuilder $dataBuilder
     * @param Logger $logger
     * @param DateTime $dateTime
     * @param Registry $registry
     */
    public function __construct(
        ApiHelper $apiHelper,
        CustomerRepositoryInterface $customerRepository,
        CustomerDataBuilder $dataBuilder,
        Logger $logger,
        DateTime $dateTime,
        Registry $registry
    ) {
        $this->apiHelper = $apiHelper;
        $this->customerRepository = $customerRepository;
        $this->dataBuilder = $dataBuilder;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->registry = $registry;
    }

    /**
     * Sync a batch of customers
     *
     * @param int[] $customerIds
     * @return array
     */
    public function syncBatch(array $customerIds): array
    {
        $stats = ['success' => 0, 'failed' => 0];

        foreach ($customerIds as $customerId) {
            try {
                $customer = $this->customerRepository->getById((int)$customerId);
                $userDetail = $this->dataBuilder->buildFromCustomer($customer);

                $response = $this->apiHelper->syncWhatsTalkUser($userDetail, 'batch_sync', (int)$customerId);

                if ($response['success']) {
                    $stats['success']++;
                } else {
                    $stats['failed']++;
                    $this->logger->warning("Batch sync failed for customer ID {$customerId}: " . $response['message']);
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->logger->error("Error syncing customer ID {$customerId}: " . $e->getMessage());
            }
        }

        return $stats;
    }
}
