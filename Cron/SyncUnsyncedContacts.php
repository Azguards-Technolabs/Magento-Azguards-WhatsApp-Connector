<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Logger\SyncProcessLogger;
use Azguards\WhatsAppConnect\Model\Config\CronConfig;
use Azguards\WhatsAppConnect\Model\Service\SyncService;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Lock\LockManagerInterface;

class SyncUnsyncedContacts
{
    private const LOCK_NAME = 'azguards_whatsapp_contact_sync_cron';
    private const LOCK_TIMEOUT = 0;
    private const BATCH_SIZE = 100;

    /**
     * @param CollectionFactory $collectionFactory
     * @param SyncService $syncService
     * @param SyncProcessLogger $logger
     * @param CronConfig $cronConfig
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly SyncService $syncService,
        private readonly SyncProcessLogger $logger,
        private readonly CronConfig $cronConfig,
        private readonly LockManagerInterface $lockManager
    ) {
    }

    /**
     * Run the unsynced contact cron job.
     *
     * @return void
     */
    public function execute(): void
    {
        $lockAcquired = false;

        try {
            $lockAcquired = $this->lockManager->lock(self::LOCK_NAME, self::LOCK_TIMEOUT);
            if (!$lockAcquired) {
                $this->logger->warning('Contact sync cron skipped because another execution is already running.');
                return;
            }

            $this->logger->info('Contact sync cron started.', [
                'schedule' => $this->cronConfig->getContactSyncSchedule()
            ]);

            $collection = $this->collectionFactory->create();
            $collection->addAttributeToFilter([
                ['attribute' => 'whatsapp_sync_status', 'null' => true],
                ['attribute' => 'whatsapp_sync_status', 'neq' => 1]
            ]);
            $collection->setPageSize(self::BATCH_SIZE);

            $customerIds = $collection->getAllIds();

            if (!empty($customerIds)) {
                $stats = $this->syncService->syncBatch($customerIds);
                $this->logger->info('Contact sync cron completed.', [
                    'schedule' => $this->cronConfig->getContactSyncSchedule(),
                    'batch_size' => self::BATCH_SIZE,
                    'total_contacts' => count($customerIds),
                    'success' => $stats['success'],
                    'failed' => $stats['failed']
                ]);
            } else {
                $this->logger->info('Contact sync cron completed with no pending contacts.', [
                    'schedule' => $this->cronConfig->getContactSyncSchedule(),
                    'batch_size' => self::BATCH_SIZE
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Contact sync cron failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        } finally {
            if ($lockAcquired) {
                $this->lockManager->unlock(self::LOCK_NAME);
            }
        }
    }
}
