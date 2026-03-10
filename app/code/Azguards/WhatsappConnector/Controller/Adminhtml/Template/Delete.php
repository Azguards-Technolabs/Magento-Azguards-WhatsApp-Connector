<?php
namespace Azguards\WhatsappConnector\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Azguards\WhatsappConnector\Api\TemplateRepositoryInterface;
use Azguards\WhatsappConnector\Model\Api\TemplateApi;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsappConnector::templates';

    protected $templateRepository;
    protected $templateApi;

    public function __construct(
        Action\Context $context,
        TemplateRepositoryInterface $templateRepository,
        TemplateApi $templateApi
    ) {
        $this->templateRepository = $templateRepository;
        $this->templateApi = $templateApi;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('template_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $template = $this->templateRepository->getById($id);

                // Delete from WhatsApp ERP API First
                $this->templateApi->deleteTemplate($id);

                // Then delete locally
                $this->templateRepository->delete($template);

                $this->messageManager->addSuccessMessage(__('You deleted the template.'));

            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the template: %1', $e->getMessage()));
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
