<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    private PageFactory $resultPageFactory;
    private CampaignService $campaignService;

    public function __construct(Context $context, PageFactory $resultPageFactory, CampaignService $campaignService)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->campaignService = $campaignService;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->campaignService->getById($id);
            } catch (LocalizedException $exception) {
                $this->messageManager->addErrorMessage(__('This campaign no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend($id ? __('Edit Campaign') : __('New Campaign'));
        return $resultPage;
    }
}
