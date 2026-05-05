<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class SendTestMessage extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::config';

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var VariableResolver
     */
    private VariableResolver $variableResolver;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param VariableResolver $variableResolver
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        VariableResolver $variableResolver
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->variableResolver = $variableResolver;
    }

    /**
     * Build a dry-run test payload from current builder values.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $sampleData = $this->variableResolver->getSampleData();

        $headerType = (string)$this->getRequest()->getParam('header_type', 'none');
        $headerText = (string)$this->getRequest()->getParam('header_text', '');
        $bodyTemplate = (string)$this->getRequest()->getParam('body_template', '');
        $footerTemplate = (string)$this->getRequest()->getParam('footer_template', '');

        $payload = [
            'event_code' => (string)$this->getRequest()->getParam('event_code', 'order_created'),
            'template_name' => (string)$this->getRequest()->getParam('template_name', ''),
            'category' => (string)$this->getRequest()->getParam('category', ''),
            'language' => (string)$this->getRequest()->getParam('language', ''),
            'header' => [
                'type' => $headerType,
                'text' => $headerType === 'text'
                    ? $this->variableResolver->resolveWithData($headerText, $sampleData)
                    : '',
            ],
            'body' => $this->variableResolver->resolveWithData($bodyTemplate, $sampleData),
            'footer' => $this->variableResolver->resolveWithData($footerTemplate, $sampleData),
        ];

        return $result->setData([
            'success' => true,
            'message' => __('Test payload prepared successfully.'),
            'payload' => $payload,
        ]);
    }
}
