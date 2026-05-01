<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;

class TemplateMeta extends Action
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateCollectionFactory $templateCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TemplateCollectionFactory $templateCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * Return template metadata for admin configuration UI.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $templateId = (string)$this->getRequest()->getParam('template_id');

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

        $headerFormat = strtoupper((string)$template->getData('header_format'));
        $headerHandle = (string)$template->getData('header_handle');

        return $result->setData([
            'success' => true,
            'template_id' => $templateId,
            'header_format' => $headerFormat,
            'header_handle' => $headerHandle,
            'has_media_header' => in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)
        ]);
    }
}
