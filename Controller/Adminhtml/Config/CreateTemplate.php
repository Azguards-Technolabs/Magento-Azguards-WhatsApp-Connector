<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Azguards\WhatsAppConnect\Model\Service\TemplateValidator;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
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
     * @var TemplateCollectionFactory
     */
    private TemplateCollectionFactory $templateCollectionFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateService $templateService
     * @param TemplateValidator $templateValidator
     * @param TemplateCollectionFactory $templateCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TemplateService $templateService,
        TemplateValidator $templateValidator,
        TemplateCollectionFactory $templateCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->templateService = $templateService;
        $this->templateValidator = $templateValidator;
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * Create or Update a Meta template and persist it locally from system-config builder values.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $data = $this->buildTemplateData();
            $this->templateValidator->validate($data);

            // Log outgoing payload for senior-level auditing
            $this->_eventManager->dispatch('whatsapp_template_config_save_before', [
                'template_data' => $data,
                'event_code'    => $this->getRequest()->getParam('event_code')
            ]);

            $data['buttons'] = json_encode($data['buttons']);

            $templateName = $data['template_name'] ?? '';
            $collection = $this->templateCollectionFactory->create()
                ->addFieldToFilter('template_name', $templateName);

            if ($collection->getSize() > 0) {
                $existingTemplate = $collection->getFirstItem();
                $template = $this->templateService->updateTemplate((int)$existingTemplate->getId(), $data);
                $message = __('Meta template updated successfully.');
            } else {
                $template = $this->templateService->createTemplate($data);
                $message = __('Meta template created successfully.');
            }

            return $result->setData([
                'success' => true,
                'message' => $message,
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
