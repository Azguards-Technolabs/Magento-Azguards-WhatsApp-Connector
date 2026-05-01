<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\CampaignService;

class SyncExternal extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    /**
     * @var CampaignService
     */
    private $campaignService;

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
     * Fetch all external campaigns and log them
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $this->campaignService->syncExternalAll();
            $this->messageManager->addSuccessMessage(
                __(
                    'Successfully retrieved campaign list from WhatTalk. Please check '
                    . 'var/log/whatsapp_connector.log (or system.log) for the detailed JSON response.'
                )
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error during sync: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
