<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $templateService;

    public function __construct(
        Context $context,
        TemplateService $templateService
    ) {
        parent::__construct($context);
        $this->templateService = $templateService;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $this->templateService->deleteTemplate((int)$id);
                $this->messageManager->addSuccessMessage(__('You deleted the template.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
