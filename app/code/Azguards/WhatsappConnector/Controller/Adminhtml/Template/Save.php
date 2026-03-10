<?php
namespace Azguards\WhatsappConnector\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Azguards\WhatsappConnector\Api\TemplateRepositoryInterface;
use Azguards\WhatsappConnector\Model\TemplateFactory;
use Azguards\WhatsappConnector\Model\Api\TemplateApi;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsappConnector::templates';

    protected $templateRepository;
    protected $templateFactory;
    protected $templateApi;

    public function __construct(
        Action\Context $context,
        TemplateRepositoryInterface $templateRepository,
        TemplateFactory $templateFactory,
        TemplateApi $templateApi
    ) {
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->templateApi = $templateApi;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $id = $this->getRequest()->getParam('template_id');

            try {
                if ($id) {
                    $template = $this->templateRepository->getById($id);
                    // Edit existing
                    $apiResponse = $this->templateApi->updateTemplate($id, $data);
                } else {
                    $template = $this->templateFactory->create();
                    // Create new
                    $apiResponse = $this->templateApi->createTemplate($data);

                    // Assuming API returns the created status
                    if (isset($apiResponse['status'])) {
                        $data['status'] = $apiResponse['status'];
                    }
                }

                $template->setData($data);
                $this->templateRepository->save($template);

                $this->messageManager->addSuccessMessage(__('You saved the template.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['template_id' => $template->getId()]);
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the template: %1', $e->getMessage()));
            }

            return $resultRedirect->setPath('*/*/edit', ['template_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
