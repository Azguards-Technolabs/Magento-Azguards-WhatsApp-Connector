<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;

class Sync extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    /**
     * @var TemplateService
     */
    private $templateService;

    /**
     * @param Context $context
     * @param TemplateService $templateService
     */
    public function __construct(
        Context $context,
        TemplateService $templateService
    ) {
        parent::__construct($context);
        $this->templateService = $templateService;
    }

    /**
     * Sync templates from API
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $summary = $this->templateService->syncTemplates();
            $this->messageManager->addSuccessMessage(
                __(
                    'Templates synchronized successfully. Created: %1, Updated: %2, Skipped: %3, Errors: %4',
                    $summary['created'],
                    $summary['updated'],
                    $summary['skipped'],
                    $summary['errors']
                )
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Failed to synchronize templates: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
