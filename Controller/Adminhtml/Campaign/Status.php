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

    /**
     * @var CampaignService
     */
    private CampaignService $campaignService;

    /**
     * @param Context $context
     * @param CampaignService $campaignService
     */
    public function __construct(
        Context $context,
        CampaignService $campaignService
    ) {
        parent::__construct($context);
        $this->campaignService = $campaignService;
    }

    /**
     * Change the selected campaign status.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
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
                $this->campaignService->changeStatus($campaign, 'pause');
                $this->messageManager->addSuccessMessage(__('Campaign has been paused and synced with WhatTalk.'));
            } elseif ($action === 'resume') {
                $this->campaignService->changeStatus($campaign, 'resume');
                $this->messageManager->addSuccessMessage(__('Campaign has been resumed and synced with WhatTalk.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error updating campaign status: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
