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

    /**
     * Save constructor
     *
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
     * Save template data
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            try {
                if (empty($data['entity_id'])) {
                    $template = $this->templateService->createTemplate($data);
                    $this->messageManager->addSuccessMessage(__('You saved the template.'));
                } else {
                    $template = $this->templateService->updateTemplate((int)$data['entity_id'], $data);
                    $this->messageManager->addSuccessMessage(__('You updated the template.'));
                }

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $template->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                // Preserve data in session to avoid clearing the form
                $this->_getSession()->setFormData($data);
                if (!empty($data['entity_id'])) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $data['entity_id']]);
                }
                return $resultRedirect->setPath('*/*/new');
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
