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

    /**
     * @var CampaignService
     */
    private CampaignService $campaignService;

    /**
     * @var \Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService
     */
    private \Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService $workerService;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Context $context
     * @param CampaignService $campaignService
     * @param \Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService $workerService
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        CampaignService $campaignService,
        \Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService $workerService,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->campaignService = $campaignService;
        $this->workerService = $workerService;
        $this->logger = $logger;
    }

    /**
     * Save a campaign from the admin form.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
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
