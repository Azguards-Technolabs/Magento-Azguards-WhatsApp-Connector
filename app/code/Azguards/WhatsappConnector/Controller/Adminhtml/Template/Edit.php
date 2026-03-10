<?php
namespace Azguards\WhatsappConnector\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Azguards\WhatsappConnector\Api\TemplateRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsappConnector::templates';

    protected $resultPageFactory;
    protected $templateRepository;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        TemplateRepositoryInterface $templateRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->templateRepository = $templateRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('template_id');
        $resultPage = $this->resultPageFactory->create();

        if ($id) {
            try {
                $template = $this->templateRepository->getById($id);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Template: %1', $template->getTemplateName()));
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This template no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Template'));
        }

        $resultPage->setActiveMenu('Azguards_WhatsappConnector::templates');
        return $resultPage;
    }
}
