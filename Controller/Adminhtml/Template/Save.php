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
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $templateService;
    private $templateValidator;
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
                    // Remove dynamic rows empty template if exists
                    if (isset($data['buttons']['record'])) {
                        unset($data['buttons']['record']);
                    }
                }

                $headerFormat = $data['header_format'] ?? 'TEXT';
                if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
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

                // Process Carousel Cards
                if (isset($data['carousel_cards']) && is_array($data['carousel_cards'])) {
                    if (isset($data['carousel_cards']['record'])) {
                        unset($data['carousel_cards']['record']);
                    }
                    $cards = array_values($data['carousel_cards']);
                    foreach ($cards as $index => &$card) {
                        $cardHeaderFormat = $card['header_format'] ?? 'TEXT';
                        if (in_array($cardHeaderFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
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
                        // Handle Buttons stringification inside card
                        if (isset($card['buttons_json']) && is_string($card['buttons_json'])) {
                            // Validate JSON
                            $decoded = json_decode($card['buttons_json'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $card['buttons'] = $decoded;
                            }
                        }

                        unset($card['header_media_upload']);
                    }
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
}
