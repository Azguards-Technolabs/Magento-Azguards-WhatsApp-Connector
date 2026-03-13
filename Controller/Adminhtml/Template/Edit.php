<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $resultPageFactory;
    private $templateService;

    /**
     * Edit constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        TemplateService $templateService
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->templateService = $templateService;
    }

    /**
     * Edit/New template action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        if ($id) {
            try {
                $this->templateService->getTemplateById($id);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('This template no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Template'));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Template'));
        }

        return $resultPage;
    }
}
