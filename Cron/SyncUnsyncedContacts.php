<?php

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Model\Service\SyncService;
use Azguards\WhatsAppConnect\Logger\Logger;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class SyncUnsyncedContacts
{
    private CollectionFactory $collectionFactory;
    private SyncService $syncService;
    private Logger $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        SyncService $syncService,
        Logger $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->syncService = $syncService;
        $this->logger = $logger;
    }

    /**
     * Execute background sync
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->logger->info('Cron SyncUnsyncedContacts started.');
            
            $collection = $this->collectionFactory->create();
            $collection->addAttributeToFilter([
                ['attribute' => 'whatsapp_sync_status', 'null' => true],
                ['attribute' => 'whatsapp_sync_status', 'neq' => 1]
            ]);
            $collection->setPageSize(100); // Process in batches of 100
            
            $customerIds = $collection->getAllIds();
            
            if (!empty($customerIds)) {
                $stats = $this->syncService->syncBatch($customerIds);
                $this->logger->info(sprintf(
                    'Cron sync completed. Success: %d, Failed: %d',
                    $stats['success'],
                    $stats['failed']
                ));
            } else {
                $this->logger->info('No unsynced contacts found.');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Error in Cron SyncUnsyncedContacts: ' . $e->getMessage());
        }
    }
}
