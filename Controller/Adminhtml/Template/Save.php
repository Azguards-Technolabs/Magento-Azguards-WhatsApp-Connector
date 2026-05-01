<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Azguards\WhatsAppConnect\Model\Service\TemplateValidator;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    /**
     * @var TemplateService
     */
    private $templateService;

    /**
     * @var TemplateValidator
     */
    private $templateValidator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Save constructor
     *
     * @param Context $context
     * @param TemplateService $templateService
     * @param TemplateValidator $templateValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        TemplateService $templateService,
        TemplateValidator $templateValidator,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->templateService = $templateService;
        $this->templateValidator = $templateValidator;
        $this->logger = $logger;
    }

    /**
     * Save template data
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $formData = $data;

            try {
                $this->logger->info('WhatsApp Template Save: Request received', [
                    'entity_id' => $data['entity_id'] ?? null,
                    'template_name' => $data['template_name'] ?? null,
                    'template_type' => $data['template_type'] ?? null,
                    'header_format' => $data['header_format'] ?? null
                ]);

                // Formatting data for validation
                if (isset($data['buttons']) && is_array($data['buttons'])) {
                    $data['buttons'] = $this->removeRecordRow($data['buttons']);
                }

                $headerFormat = $data['header_format'] ?? 'TEXT';
                if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    $this->applyTopLevelMediaData($data, $formData, $headerFormat);
                }

                if (isset($data['carousel_cards']) && is_array($data['carousel_cards'])) {
                    $cards = $this->normalizeCarouselCards($data, $formData);
                    $data['carousel_cards'] = json_encode($cards);
                }

                unset($data['header_media_upload']);

                $this->templateValidator->validate($data);
                $this->logger->info('WhatsApp Template Save: Validation passed', [
                    'template_name' => $data['template_name'] ?? null,
                    'template_type' => $data['template_type'] ?? null
                ]);

                if (isset($data['buttons']) && is_array($data['buttons'])) {
                    $data['buttons'] = json_encode(array_values($data['buttons']));
                }

                if (isset($data['body_examples']) && is_array($data['body_examples'])) {
                    if (isset($data['body_examples']['record'])) {
                        unset($data['body_examples']['record']);
                    }
                    $examples = array_column(array_values($data['body_examples']), 'example');
                    $data['body_examples_json'] = json_encode($examples);
                }

                if (empty($data['entity_id'])) {
                    $template = $this->templateService->createTemplate($data);
                    $this->logger->info('WhatsApp Template Save: Create completed', [
                        'template_name' => $data['template_name'] ?? null,
                        'entity_id' => $template->getId(),
                        'template_id' => $template->getTemplateId()
                    ]);
                    $this->messageManager->addSuccessMessage(__('You saved the template.'));
                } else {
                    $template = $this->templateService->updateTemplate((int)$data['entity_id'], $data);
                    $this->logger->info('WhatsApp Template Save: Update completed', [
                        'template_name' => $data['template_name'] ?? null,
                        'entity_id' => $template->getId(),
                        'template_id' => $template->getTemplateId()
                    ]);
                    $this->messageManager->addSuccessMessage(__('You updated the template.'));
                }

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $template->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->logger->error('WhatsApp Template Save: Failed', [
                    'template_name' => $data['template_name'] ?? null,
                    'entity_id' => $data['entity_id'] ?? null,
                    'message' => $e->getMessage()
                ]);
                $this->messageManager->addErrorMessage($e->getMessage());
                // Preserve data in session to avoid clearing the form
                $this->_getSession()->setFormData($formData);
                if (!empty($data['entity_id'])) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $data['entity_id']]);
                }
                return $resultRedirect->setPath('*/*/new');
            }
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Remove UI component record rows from dynamic-grid payloads.
     *
     * @param array $rows
     * @return array
     */
    private function removeRecordRow(array $rows): array
    {
        if (isset($rows['record'])) {
            unset($rows['record']);
        }

        return $rows;
    }

    /**
     * Populate top-level media values from upload payload.
     *
     * @param array $data
     * @param array $formData
     * @param string $headerFormat
     * @return void
     */
    private function applyTopLevelMediaData(array &$data, array &$formData, string $headerFormat): void
    {
        if (empty($data['header_handle']) && !empty($data['header_media_upload'][0]['document_id'])) {
            $data['header_handle'] = $data['header_media_upload'][0]['document_id'];
            $formData['header_handle'] = $data['header_handle'];
        }

        if (empty($data['header_image']) && !empty($data['header_media_upload'][0]['preview_link'])) {
            $data['header_image'] = $data['header_media_upload'][0]['preview_link'];
            $formData['header_image'] = $data['header_image'];
        }

        $this->logger->info('WhatsApp Template Save: Using stored top-level media values', [
            'template_name' => $data['template_name'] ?? null,
            'header_format' => $headerFormat,
            'document_id' => $data['header_handle'] ?? null,
            'preview_link' => $data['header_image'] ?? null
        ]);
    }

    /**
     * Normalize carousel card payload for validation and persistence.
     *
     * @param array $data
     * @param array $formData
     * @return array
     */
    private function normalizeCarouselCards(array $data, array &$formData): array
    {
        $cards = array_values($this->removeRecordRow($data['carousel_cards']));

        foreach ($cards as $index => &$card) {
            $cardHeaderFormat = $card['header_format'] ?? 'TEXT';
            if (in_array($cardHeaderFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $this->applyCarouselMediaData($data, $card, $formData, $index, $cardHeaderFormat);
            }

            if (isset($card['buttons']) && is_array($card['buttons'])) {
                $card['buttons'] = $this->normalizeCardButtons($card['buttons']);
            } elseif (isset($card['buttons_json']) && is_string($card['buttons_json'])) {
                $decoded = json_decode($card['buttons_json'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $card['buttons'] = $decoded;
                }
            }

            unset($card['header_media_upload'], $card['buttons_json']);
        }

        return $cards;
    }

    /**
     * Populate carousel card media values from upload payload.
     *
     * @param array $data
     * @param array $card
     * @param array $formData
     * @param int $index
     * @param string $cardHeaderFormat
     * @return void
     */
    private function applyCarouselMediaData(
        array $data,
        array &$card,
        array &$formData,
        int $index,
        string $cardHeaderFormat
    ): void {
        if (empty($card['header_handle']) && !empty($card['header_media_upload'][0]['document_id'])) {
            $card['header_handle'] = $card['header_media_upload'][0]['document_id'];
            $formData['carousel_cards'][$index]['header_handle'] = $card['header_handle'];
        }

        if (empty($card['header_image']) && !empty($card['header_media_upload'][0]['preview_link'])) {
            $card['header_image'] = $card['header_media_upload'][0]['preview_link'];
            $formData['carousel_cards'][$index]['header_image'] = $card['header_image'];
        }

        $this->logger->info('WhatsApp Template Save: Using stored carousel media values', [
            'template_name' => $data['template_name'] ?? null,
            'card_index' => $index,
            'header_format' => $cardHeaderFormat,
            'document_id' => $card['header_handle'] ?? null,
            'preview_link' => $card['header_image'] ?? null
        ]);
    }

    /**
     * Normalize carousel card buttons.
     *
     * @param array $buttons
     * @return array
     */
    private function normalizeCardButtons(array $buttons): array
    {
        $buttons = $this->removeRecordRow($buttons);

        return array_values(array_filter(
            $buttons,
            static fn ($button) => is_array($button)
                && !empty(array_filter($button, static fn ($value) => $value !== '' && $value !== null))
        ));
    }
}
