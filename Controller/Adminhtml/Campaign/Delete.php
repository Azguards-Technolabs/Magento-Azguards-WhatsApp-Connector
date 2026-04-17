<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    private CampaignService $campaignService;

    public function __construct(Context $context, CampaignService $campaignService)
    {
        parent::__construct($context);
        $this->campaignService = $campaignService;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a campaign to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->campaignService->deleteById($id);
            $this->messageManager->addSuccessMessage(__('You deleted the campaign.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
