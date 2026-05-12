<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class RefreshTemplateStatus extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::config';

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var TemplateCollectionFactory
     */
    private TemplateCollectionFactory $templateCollectionFactory;

    /**
     * @var TemplateService
     */
    private TemplateService $templateService;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateCollectionFactory $templateCollectionFactory
     * @param TemplateService $templateService
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TemplateCollectionFactory $templateCollectionFactory,
        TemplateService $templateService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->templateService = $templateService;
    }

    /**
     * Refresh local template metadata from the API and return latest status.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        $templateId = trim((string)$this->getRequest()->getParam('template_id'));

        if ($templateId === '') {
            return $result->setData([
                'success' => false,
                'message' => __('template_id is required')
            ]);
        }

        $collection = $this->templateCollectionFactory->create();
        $collection->addFieldToFilter('template_id', $templateId);
        $template = $collection->getFirstItem();

        if (!$template || !$template->getId()) {
            return $result->setData([
                'success' => false,
                'message' => __('Template not found')
            ]);
        }

        try {
            $refreshed = $this->templateService->refreshTemplateStatus(
                $templateId,
                (string)$template->getData('template_name')
            );

            if ($refreshed === null) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Template was not found in Meta sync response.'),
                    'template_id' => $templateId,
                    'status' => (string)$template->getData('status'),
                ]);
            }

            return $result->setData([
                'success' => true,
                'message' => __('Template status refreshed successfully.'),
                'template_id' => $refreshed['template_id'] ?? $templateId,
                'status' => $refreshed['status'] ?? (string)$template->getData('status'),
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'template_id' => $templateId,
                'status' => (string)$template->getData('status'),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Unable to refresh template status right now.'),
                'template_id' => $templateId,
                'status' => (string)$template->getData('status'),
            ]);
        }
    }
}
