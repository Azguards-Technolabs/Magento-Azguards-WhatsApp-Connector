<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;

class Save extends Action
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
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            try {
                if (empty($data['entity_id'])) {
                    $this->templateService->createTemplate($data);
                    $this->messageManager->addSuccessMessage(__('You saved the template.'));
                } else {
                    $this->templateService->updateTemplate((int)$data['entity_id'], $data);
                    $this->messageManager->addSuccessMessage(__('You updated the template.'));
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['id' => $data['entity_id'] ?? null]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
