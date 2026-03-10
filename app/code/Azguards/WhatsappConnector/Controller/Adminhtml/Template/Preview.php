<?php
namespace Azguards\WhatsappConnector\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Azguards\WhatsappConnector\Api\TemplateRepositoryInterface;

class Preview extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsappConnector::templates';

    protected $templateRepository;

    public function __construct(
        Action\Context $context,
        TemplateRepositoryInterface $templateRepository
    ) {
        $this->templateRepository = $templateRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('template_id');
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if ($id) {
            try {
                $template = $this->templateRepository->getById($id);
                $resultPage->getConfig()->getTitle()->prepend(__('Preview Template: %1', $template->getTemplateName()));

                // For simplicity, we assign data to registry or use a block directly. Let's create a dynamic block content.
                $block = $resultPage->getLayout()->createBlock(\Magento\Backend\Block\Template::class)
                    ->setTemplate('Azguards_WhatsappConnector::template/preview.phtml')
                    ->setData('template_data', $template);

                $resultPage->getLayout()->addBlock($block, 'content');

                return $resultPage;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $this->messageManager->addErrorMessage(__('Invalid template ID.'));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
