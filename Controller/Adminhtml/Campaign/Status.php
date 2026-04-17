<?php

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Status extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    private CampaignService $campaignService;

    public function __construct(
        Context $context,
        CampaignService $campaignService
    ) {
        parent::__construct($context);
        $this->campaignService = $campaignService;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $action = $this->getRequest()->getParam('action');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid campaign ID.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $campaign = $this->campaignService->getById($id);
            
            if ($action === 'pause') {
                $campaign->setStatus(Campaign::STATUS_PAUSED);
                $this->messageManager->addSuccessMessage(__('Campaign has been paused.'));
            } elseif ($action === 'resume') {
                $campaign->setStatus(Campaign::STATUS_PROCESSING);
                $this->messageManager->addSuccessMessage(__('Campaign has been resumed.'));
            }

            $campaign->getResource()->save($campaign);

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error updating campaign status: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
