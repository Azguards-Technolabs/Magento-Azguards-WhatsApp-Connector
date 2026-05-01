<?php

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Customer;

use Azguards\WhatsAppConnect\Model\Service\SyncService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

class SyncAll extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Magento_Customer::manage';

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var SyncService
     */
    private SyncService $syncService;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param SyncService $syncService
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        SyncService $syncService
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->syncService = $syncService;
    }

    /**
     * Sync unsynced customers in a limited admin-triggered batch.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $collection = $this->collectionFactory->create();
            // Select customers who are not yet synced
            $collection->addAttributeToFilter([
                ['attribute' => 'whatsapp_sync_status', 'null' => true],
                ['attribute' => 'whatsapp_sync_status', 'neq' => 1]
            ]);

            // Keep UI responsive: process a limited batch per click.
            $limit = 200;
            $totalToSync = (int)$collection->getSize();
            if ($totalToSync <= 0) {
                $this->messageManager->addNoticeMessage(__('All customers are already synced.'));
            } else {
                $collection->setPageSize($limit);
                $collection->setCurPage(1);
                $batchIds = $collection->getAllIds();

                $stats = $this->syncService->syncBatch($batchIds);

                if ($stats['success'] > 0) {
                    $this->messageManager->addSuccessMessage(
                        __('Successfully synced %1 unsynced customer(s).', $stats['success'])
                    );
                }
                
                if ($totalToSync > $limit) {
                    $remainingMessage = __(
                        '%1 more customers are remaining. They will be synced automatically by the Cron job '
                        . 'or you can click Bulk Sync again.',
                        ($totalToSync - $limit)
                    );
                    $this->messageManager->addNoticeMessage($remainingMessage);
                }

                if ($stats['failed'] > 0) {
                    $this->messageManager->addErrorMessage(
                        __('Failed to sync %1 customer(s). Check logs for details.', $stats['failed'])
                    );
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred during bulk sync: %1', $e->getMessage())
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
