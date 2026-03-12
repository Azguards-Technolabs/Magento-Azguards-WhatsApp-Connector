<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Azguards\WhatsAppConnect\Model\Service\TemplateValidator;
use Azguards\WhatsAppConnect\Model\Service\MediaUploadService;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $templateService;
    private $templateValidator;
    private $mediaUploadService;

    /**
     * Save constructor
     *
     * @param Context $context
     * @param TemplateService $templateService
     * @param TemplateValidator $templateValidator
     * @param MediaUploadService $mediaUploadService
     */
    public function __construct(
        Context $context,
        TemplateService $templateService,
        TemplateValidator $templateValidator,
        MediaUploadService $mediaUploadService
    ) {
        parent::__construct($context);
        $this->templateService = $templateService;
        $this->templateValidator = $templateValidator;
        $this->mediaUploadService = $mediaUploadService;
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
            try {
                // Formatting data for validation
                if (isset($data['buttons']) && is_array($data['buttons'])) {
                    // Remove dynamic rows empty template if exists
                    if (isset($data['buttons']['record'])) {
                        unset($data['buttons']['record']);
                    }
                }

                $headerFormat = $data['header_format'] ?? 'TEXT';
                if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    if (isset($data['header_media_upload']) && is_array($data['header_media_upload'])) {
                        $uploadResult = $this->mediaUploadService->processFileFromTmp($data['header_media_upload'], $headerFormat);
                        if (!empty($uploadResult['document_id'])) {
                            $data['header_handle'] = $uploadResult['document_id'];
                            $data['header_image'] = $uploadResult['preview_link'];
                        }
                    }
                }

                // Process Carousel Cards
                if (isset($data['carousel_cards']) && is_array($data['carousel_cards'])) {
                    if (isset($data['carousel_cards']['record'])) {
                        unset($data['carousel_cards']['record']);
                    }
                    $cards = array_values($data['carousel_cards']);
                    foreach ($cards as &$card) {
                        $cardHeaderFormat = $card['header_format'] ?? 'TEXT';
                        if (in_array($cardHeaderFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                            if (isset($card['header_media_upload']) && is_array($card['header_media_upload'])) {
                                $uploadResult = $this->mediaUploadService->processFileFromTmp($card['header_media_upload'], $cardHeaderFormat);
                                if (!empty($uploadResult['document_id'])) {
                                    $card['header_handle'] = $uploadResult['document_id'];
                                    $card['header_image'] = $uploadResult['preview_link'];
                                }
                            }
                        }
                        // Handle Buttons stringification inside card
                        if (isset($card['buttons_json']) && is_string($card['buttons_json'])) {
                            // Validate JSON
                            $decoded = json_decode($card['buttons_json'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $card['buttons'] = $decoded;
                            }
                        }
                    }
                    $data['carousel_cards'] = json_encode($cards);
                }

                $this->templateValidator->validate($data);

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
                    $this->messageManager->addSuccessMessage(__('You saved the template.'));
                } else {
                    $template = $this->templateService->updateTemplate((int)$data['entity_id'], $data);
                    $this->messageManager->addSuccessMessage(__('You updated the template.'));
                }

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $template->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                // Preserve data in session to avoid clearing the form
                $this->_getSession()->setFormData($data);
                if (!empty($data['entity_id'])) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $data['entity_id']]);
                }
                return $resultRedirect->setPath('*/*/new');
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
