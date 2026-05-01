<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Retry extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    /**
     * @var CampaignService
     */
    private CampaignService $campaignService;

    /**
     * @var CampaignWorkerService
     */
    private CampaignWorkerService $workerService;

    /**
     * @param Context $context
     * @param CampaignService $campaignService
     * @param CampaignWorkerService $workerService
     */
    public function __construct(
        Context $context,
        CampaignService $campaignService,
        CampaignWorkerService $workerService
    ) {
        parent::__construct($context);
        $this->campaignService = $campaignService;
        $this->workerService = $workerService;
    }

    /**
     * Retry failed items for the selected campaign.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid campaign ID.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $campaign = $this->campaignService->getById($id);
            $this->campaignService->retryFailedItems($campaign);

            // Trigger worker immediately to process retries
            $this->workerService->execute('Retry');

            $this->messageManager->addSuccessMessage(
                __('Campaign retry initiated. Failed messages are being re-sent.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
