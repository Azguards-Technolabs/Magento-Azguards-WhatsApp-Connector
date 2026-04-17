<?php

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Customer;

use Azguards\WhatsAppConnect\Model\Service\SyncService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;

class MassSync extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Customer::manage';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private SyncService $syncService;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SyncService $syncService
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->syncService = $syncService;
    }

    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $customerIds = $collection->getAllIds();

            if (empty($customerIds)) {
                $this->messageManager->addErrorMessage(__('Please select customers to sync.'));
            } else {
                $stats = $this->syncService->syncBatch($customerIds);
                
                if ($stats['success'] > 0) {
                    $this->messageManager->addSuccessMessage(
                        __('Successfully synced %1 customer(s) to WhatsApp.', $stats['success'])
                    );
                }
                
                if ($stats['failed'] > 0) {
                    $this->messageManager->addErrorMessage(
                        __('Failed to sync %1 customer(s). Check logs for details.', $stats['failed'])
                    );
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred during mass sync: %1', $e->getMessage()));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('customer/index');
    }
}
