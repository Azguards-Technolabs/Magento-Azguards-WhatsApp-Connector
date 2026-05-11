<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Psr\Log\LoggerInterface;

class Duplicate extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    /**
     * @var TemplateService
     */
    private $templateService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param TemplateService $templateService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        TemplateService $templateService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->templateService = $templateService;
        $this->logger = $logger;
    }

    /**
     * Duplicate template
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a template to duplicate.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $template = $this->templateService->getTemplateById((int)$id);
            $data = $template->getData();

            // Prepare data for new template
            // Remove unique/system identifiers
            unset($data['entity_id']);
            unset($data['template_id']);
            unset($data['created_at']);
            unset($data['updated_at']);

            // Generate a new template name
            $data['template_name'] = $this->generateUniqueName($data['template_name']);

            // Set initial status
            $data['status'] = 'PENDING';

            // Store data in session to be picked up by DataProvider
            $this->_getSession()->setWhatsAppDuplicateData($data);

            return $resultRedirect->setPath('*/*/new', ['is_duplicate' => 1]);
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Template Duplicate: Failed', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }
    }

    /**
     * Generate a new template name based on the original name.
     *
     * @param string $name
     * @return string
     */
    private function generateUniqueName(string $name): string
    {
        $suffix = '_copy';
        $newName = $name . $suffix;

        if (strlen($newName) > 50) {
            $newName = substr($name, 0, 50 - strlen($suffix)) . $suffix;
        }

        return $newName;
    }
}
