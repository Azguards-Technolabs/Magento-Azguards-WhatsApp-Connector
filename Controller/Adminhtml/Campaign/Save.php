<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    private CampaignService $campaignService;
    private \Azguards\WhatsAppConnect\Model\Service\CampaignSchedulerService $schedulerService;
    private \Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService $workerService;
    private Logger $logger;

    public function __construct(
        Context $context,
        CampaignService $campaignService,
        \Azguards\WhatsAppConnect\Model\Service\CampaignSchedulerService $schedulerService,
        \Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService $workerService,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->campaignService = $campaignService;
        $this->schedulerService = $schedulerService;
        $this->workerService = $workerService;
        $this->logger = $logger;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $campaign = $this->campaignService->save($data);
            $this->messageManager->addSuccessMessage(__('You saved the campaign.'));

            // Immediate Send Flow (Senior Architect: Triggering background processes synchronously for small batches)
            if (!(bool)$campaign->getData('is_scheduled')) {
                $this->logger->info('Triggering Immediate Send for Campaign ID: ' . $campaign->getId());
                $this->schedulerService->processCampaign($campaign, 'Immediate');
                $this->workerService->execute('Immediate');
                $this->messageManager->addNoticeMessage(__('Immediate campaign processing triggered. Check logs for details.'));
            }

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $campaign->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $exception) {
            $this->logger->error('Campaign save failed: ' . $exception->getMessage());
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_getSession()->setFormData($data);
            if (!empty($data['entity_id'])) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $data['entity_id']]);
            }
            return $resultRedirect->setPath('*/*/new');
        }
    }
}
