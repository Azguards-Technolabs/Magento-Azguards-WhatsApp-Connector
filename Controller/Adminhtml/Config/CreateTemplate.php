<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Azguards\WhatsAppConnect\Model\Service\TemplateValidator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class CreateTemplate extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::config';

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var TemplateService
     */
    private TemplateService $templateService;

    /**
     * @var TemplateValidator
     */
    private TemplateValidator $templateValidator;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateService $templateService
     * @param TemplateValidator $templateValidator
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TemplateService $templateService,
        TemplateValidator $templateValidator
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->templateService = $templateService;
        $this->templateValidator = $templateValidator;
    }

    /**
     * Create a Meta template and persist it locally from system-config builder values.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $data = $this->buildTemplateData();
            $this->templateValidator->validate($data);
            $data['buttons'] = json_encode($data['buttons']);

            $template = $this->templateService->createTemplate($data);

            return $result->setData([
                'success' => true,
                'message' => __('Template created successfully.'),
                'entity_id' => $template->getId(),
                'template_id' => $template->getTemplateId(),
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Unable to create template right now.'),
            ]);
        }
    }

    /**
     * Map config-builder values to the existing template service payload.
     *
     * @return array<string, mixed>
     */
    private function buildTemplateData(): array
    {
        $headerType = (string)$this->getRequest()->getParam('header_type', 'none');
        $buttonsJson = (string)$this->getRequest()->getParam('buttons_json', '[]');
        $buttons = json_decode($buttonsJson, true);

        if (!is_array($buttons)) {
            $buttons = [];
        }

        return [
            'template_name' => (string)$this->getRequest()->getParam('template_name', ''),
            'template_type' => $headerType === 'image' ? 'MEDIA' : 'TEXT',
            'template_category' => (string)$this->getRequest()->getParam('category', ''),
            'language' => (string)$this->getRequest()->getParam('language', ''),
            'header_format' => $headerType === 'image' ? 'IMAGE' : 'TEXT',
            'header' => $headerType === 'text' ? (string)$this->getRequest()->getParam('header_text', '') : '',
            'header_handle' => (string)$this->getRequest()->getParam('header_handle', ''),
            'header_image' => (string)$this->getRequest()->getParam('header_image', ''),
            'body' => (string)$this->getRequest()->getParam('body_template', ''),
            'body_examples_json' => (string)$this->getRequest()->getParam('body_examples_json', ''),
            'footer' => (string)$this->getRequest()->getParam('footer_template', ''),
            'buttons' => array_values(array_filter($buttons, static function ($button): bool {
                return is_array($button) && !empty($button['type']) && !empty($button['text']);
            })),
        ];
    }
}
