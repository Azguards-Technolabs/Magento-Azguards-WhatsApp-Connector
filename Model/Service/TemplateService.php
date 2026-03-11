<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Model\Api\TemplateApi;
use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterfaceFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

class TemplateService
{
    private $templateApi;
    private $templateRepository;
    private $templateFactory;
    private $logger;
    private $dataObjectHelper;
    private $searchCriteriaBuilderFactory;

    public function __construct(
        TemplateApi $templateApi,
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->templateApi = $templateApi;
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Create template
     *
     * @param array $data
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
     * @throws LocalizedException
     */
    public function createTemplate(array $data): \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
    {
        try {
            $apiData = $this->prepareApiData($data);
            $this->logger->info("ERP Create Template Payload: " . json_encode($apiData));

            // Execute actual API call
            $apiResponse = $this->templateApi->createTemplate($apiData);

            // Log response to debug
            $this->logger->info("ERP Create Template Response: " . json_encode($apiResponse));

            // Extract ID from different possible API response shapes
            $externalId = $apiResponse['result']['data'][0]['id'] ?? 
                         $apiResponse['result']['id'] ?? 
                         $apiResponse['id'] ?? 
                         $apiResponse['template_id'] ?? 
                         null;

            if (empty($externalId)) {
                $errorMsg = $apiResponse['message'] ?? $apiResponse['error']['message'] ?? 'Unknown ERP Error';
                throw new LocalizedException(__('ERP Response but no ID: %1', $errorMsg));
            }

            $template = $this->templateFactory->create();
            
            // Ensure we don't pass an empty entity_id, which can confuse Magento's save logic
            if (isset($data['entity_id'])) {
                unset($data['entity_id']);
            }

            // Ensure buttons are encoded as JSON string for storage
            if (isset($data['buttons']) && is_array($data['buttons'])) {
                $data['buttons'] = json_encode($data['buttons']);
            }

            $this->dataObjectHelper->populateWithArray(
                $template,
                $data,
                \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
            );
            $template->setTemplateId($externalId);
            $template->setStatus('PENDING');

            $this->logger->info("Template data before save: " . json_encode($template->getData()));

            $this->templateRepository->save($template);
            $this->logger->info("Template saved locally. Entity ID: " . $template->getId());

            return $template;
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to create template: " . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Update template
     *
     * @param int $entityId
     * @param array $data
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
     * @throws LocalizedException
     */
    public function updateTemplate(int $entityId, array $data): \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
    {
        try {
            $template = $this->templateRepository->getById($entityId);
            $apiData = $this->prepareApiData($data);

            $templateId = $template->getTemplateId();
            if (!$templateId) {
                throw new LocalizedException(__('Template ID is missing. Cannot update in ERP.'));
            }

            // Execute actual API call
            $this->templateApi->updateTemplate($templateId, $apiData);
            $this->logger->info("Template updated in API successfully: " . $templateId);

            // Ensure buttons are encoded as JSON string for storage
            if (isset($data['buttons']) && is_array($data['buttons'])) {
                $data['buttons'] = json_encode($data['buttons']);
            }

            // Merge existing data with new data
            $this->dataObjectHelper->populateWithArray(
                $template,
                $data,
                \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
            );

            $this->templateRepository->save($template);
            $this->logger->info("Template updated locally: " . $template->getId());

            return $template;
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to update template: " . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Delete template
     *
     * @param int $entityId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteTemplate(int $entityId): bool
    {
        try {
            $template = $this->templateRepository->getById($entityId);
            $templateId = $template->getTemplateId();

            if ($templateId) {
                // Execute actual API call
                $this->templateApi->deleteTemplate($templateId);
                $this->logger->info("Template deleted in API successfully: " . $templateId);
            }

            $this->templateRepository->delete($template);
            $this->logger->info("Template deleted locally: " . $entityId);

            return true;
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete template: " . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Get template by ID
     *
     * @param int $entityId
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
     */
    public function getTemplateById(int $entityId): \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
    {
        return $this->templateRepository->getById($entityId);
    }

    /**
     * Sync ALL templates from API — batches of 10, skip already-saved ones
     *
     * @return array ['created' => N, 'skipped' => N, 'errors' => N]
     */
    public function syncTemplates(): array
    {
        $summary = [
            'created' => 0,
            'skipped' => 0,
            'errors'  => 0,
        ];

        $batchSize = 10;
        $page      = 1;

        try {
            do {
                $this->logger->info(sprintf('WhatsApp Sync: Fetching page %d (batch %d)', $page, $batchSize));

                $result    = $this->templateApi->getTemplatesPaginated($page, $batchSize);
                $templates = $result['data'];
                $hasMore   = $result['hasMore'];

                if (empty($templates)) {
                    $this->logger->info('WhatsApp Sync: No templates on page ' . $page . ', done.');
                    break;
                }

                foreach ($templates as $templateData) {
                    try {
                        $templateId = $templateData['id'] ?? $templateData['template_id'] ?? null;
                        if (!$templateId) {
                            continue;
                        }

                        // Skip if already exists in DB
                        $existing = $this->getTemplateByExternalId((string)$templateId);
                        if ($existing !== null) {
                            $summary['skipped']++;
                            $this->logger->info(sprintf('WhatsApp Sync: Skipped (already exists) %s', (string)$templateId));
                            continue;
                        }

                        // New template — map and save
                        $mappedData = $this->mapApiResponseToLocal($templateData);

                        $template = $this->templateFactory->create();
                        $this->dataObjectHelper->populateWithArray(
                            $template,
                            $mappedData,
                            \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
                        );

                        $this->templateRepository->save($template);
                        $summary['created']++;
                        $this->logger->info(sprintf('WhatsApp Sync: Created template %s', (string)$templateId));

                    } catch (\Exception $e) {
                        $summary['errors']++;
                        $this->logger->error(sprintf(
                            'WhatsApp Sync: Error on template %s: %s',
                            isset($templateId) ? (string)$templateId : 'unknown',
                            $e->getMessage()
                        ));
                    }
                }

                $page++;
                if ($page > 500) {
                    $this->logger->warning('WhatsApp Sync: Safety limit 500 pages reached.');
                    break;
                }

            } while ($hasMore);

            $this->logger->info(sprintf(
                'WhatsApp Sync done — Created: %d, Skipped: %d, Errors: %d',
                $summary['created'], $summary['skipped'], $summary['errors']
            ));

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Sync fatal: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to sync templates: %1', $e->getMessage()));
        }

        return $summary;
    }

    /**
     * Get template by its external (API) ID or fallback to name and language
     *
     * @param string $templateId
     * @param string|null $templateName
     * @param string|null $language
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface|null
     */
    private function getTemplateByExternalId(
        string $templateId,
        ?string $templateName = null,
        ?string $language = null
    ): ?\Azguards\WhatsAppConnect\Api\Data\TemplateInterface {
        // First try to find by template_id using the repository (which uses search criteria)
        // But let's log what we are searching for
        $this->logger->info(sprintf("WhatsApp Sync: Searching for template_id: %s", $templateId));

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('template_id', $templateId)
            ->create();

        $collection = $this->templateRepository->getList($searchCriteria);
        $items = $collection->getItems();

        if (count($items) > 0) {
            $item = reset($items);
            $this->logger->info(sprintf("WhatsApp Sync: Found by template_id. EntityID: %s", $item->getId()));
            return $item;
        }

        // Fallback: search by name and language if provided
        if ($templateName && $language) {
            $this->logger->info(sprintf("WhatsApp Sync: Fallback search for name: %s, lang: %s", $templateName, $language));
            
            // Using separate filters in different groups to ensure AND behavior
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter('template_name', $templateName)
                ->create();
            
            // Adding name filter
            $filter1 = $this->searchCriteriaBuilderFactory->create()->addFilter('template_name', $templateName)->create()->getFilterGroups()[0];
            $filter2 = $this->searchCriteriaBuilderFactory->create()->addFilter('language', $language)->create()->getFilterGroups()[0];
            
            $searchCriteria->setFilterGroups([$filter1, $filter2]);

            $collection = $this->templateRepository->getList($searchCriteria);

            if ($collection->getTotalCount() > 0) {
                $item = reset($collection->getItems());
                $this->logger->info(sprintf("WhatsApp Sync: Found by name/lang. EntityID: %s", $item->getId()));
                return $item;
            }
        }

        $this->logger->info("WhatsApp Sync: No existing template found.");
        return null;
    }

    /**
     * Map API response to local data
     *
     * @param array $apiData
     * @return array
     */
    private function mapApiResponseToLocal(array $apiData): array
    {
        $header = null;
        $body = '';
        $footer = null;
        $buttonType = null;
        $buttonText = null;
        $buttonUrl = null;
        $buttonPhone = null;

        if (isset($apiData['components']) && is_array($apiData['components'])) {
            foreach ($apiData['components'] as $component) {
                switch ($component['componentType']) {
                    case 'HEADER':
                        $header = $component['componentData'] ?? null;
                        break;
                    case 'BODY':
                        $body = $component['componentData'] ?? '';
                        break;
                    case 'FOOTER':
                        $footer = $component['componentData'] ?? null;
                        break;
                    case 'BUTTONS':
                        $extractedButtons = [];
                        $buttonsData = $component['buttons'] ?? $component['componentData'] ?? [];
                        if (is_array($buttonsData)) {
                            foreach ($buttonsData as $btn) {
                                $extractedButtons[] = [
                                    'type'  => strtolower($btn['type'] ?? ''),
                                    'text'  => $btn['text'] ?? '',
                                    'value' => $btn['url'] ?? $btn['phoneNumber'] ?? $btn['value'] ?? ''
                                ];
                            }
                        }
                        $buttons = json_encode($extractedButtons);
                        break;
                }
            }
        }

        // Fallback to top-level fields if components are missing or empty
        $header = $this->extractStringContent($header ?? ($apiData['templateHeaderText'] ?? null));
        $body = $this->extractStringContent($body ?: ($apiData['templateBodyText'] ?? '')) ?: '';
        $footer = $this->extractStringContent($footer ?? ($apiData['templateFooterText'] ?? null));

        // Extract category name and normalize... (original logic remains)

        // Extract category name and normalize it to match UI options (Marketing, Utility, Authentication)
        $category = $apiData['categoryName'] ?? '';
        if (empty($category) && isset($apiData['category']['name'])) {
            $category = $apiData['category']['name'];
        } elseif (empty($category) && isset($apiData['templateCategory'])) {
            $category = $apiData['templateCategory'];
        }

        if ($category) {
            $category = ucfirst(strtolower($category));
            if ($category === 'Auth') {
                $category = 'Authentication';
            }
        }

        // Extract language code
        $language = $apiData['languageCode'] ?? '';
        if (empty($language) && isset($apiData['language']['code'])) {
            $language = $apiData['language']['code'];
        } elseif (empty($language) && isset($apiData['language'])) {
            $language = is_array($apiData['language']) ? ($apiData['language']['code'] ?? 'en_US') : $apiData['language'];
        }

        // Normalize button type if it was set elsewhere
        if ($buttonType) {
            $buttonType = strtolower($buttonType);
        }

        return [
            'template_id' => $apiData['id'] ?? $apiData['template_id'] ?? '',
            'template_name' => $apiData['templateName'] ?? $apiData['name'] ?? '',
            'template_type' => $apiData['templateHeaderType'] ?? $apiData['type'] ?? 'TEXT',
            'template_category' => $category,
            'language' => $language ?: 'en_US',
            'status' => $apiData['status'] ?? 'APPROVED',
            'header' => $header,
            'body' => $body,
            'footer' => $footer,
            'buttons' => $buttons ?? null
        ];
    }

    /**
     * Prepare API data for creating/updating template in ERP
     *
     * @param array $data
     * @return array
     */
    private function prepareApiData(array $data): array
    {
        $payload = [
            'name'     => $data['template_name'] ?? '',
            'language' => $data['language'] ?? 'en_US',
            'type'     => $data['template_type'] ?? 'TEXT',
            'category' => strtoupper($data['template_category'] ?? 'UTILITY'),
        ];

        // 1. Header
        if (!empty($data['header'])) {
            $result = $this->transformContentWithVariables($data['header']);
            $payload['header'] = [
                'type'   => 'HEADER',
                'format' => 'TEXT',
                'text'   => $result['text']
            ];
            if (!empty($result['params'])) {
                $payload['header']['param'] = $result['params'];
            }
        }

        // 2. Body
        if (!empty($data['body'])) {
            $result = $this->transformContentWithVariables($data['body']);
            $payload['body'] = [
                'type'   => 'BODY',
                'format' => 'TEXT',
                'text'   => $result['text']
            ];
            if (!empty($result['params'])) {
                $payload['body']['param'] = $result['params'];
            }
        }

        // 3. Footer
        if (!empty($data['footer'])) {
            $payload['footer'] = [
                'type' => 'FOOTER',
                'text' => $data['footer']
            ];
        }

        // 4. Buttons
        if (!empty($data['buttons']) && is_array($data['buttons'])) {
            $formattedButtons = [];
            foreach ($data['buttons'] as $btnData) {
                if (empty($btnData['text']) || (empty($btnData['type']) || $btnData['type'] === 'none')) {
                    continue;
                }

                $btnType = strtoupper($btnData['type']);
                if ($btnType === 'PHONE') {
                    $btnType = 'PHONE_NUMBER';
                }

                $button = [
                    'type' => $btnType,
                    'text' => $btnData['text']
                ];

                if ($btnType === 'URL' && !empty($btnData['value'])) {
                    $button['value'] = $btnData['value'];
                } elseif ($btnType === 'PHONE_NUMBER' && !empty($btnData['value'])) {
                    $button['value'] = $btnData['value'];
                }

                $formattedButtons[] = $button;
            }

            if (!empty($formattedButtons)) {
                $payload['buttons'] = $formattedButtons;
            }
        }

        return $payload;
    }

    /**
     * Transform descriptive variables to numeric ones and extract params
     * 
     * Example: "Hello {{Customer Name}}" -> ["text" => "Hello {{1}}", "params" => ["Customer Name"]]
     *
     * @param string $content
     * @return array
     */
    private function transformContentWithVariables(string $content): array
    {
        $params = [];
        $transformedText = preg_replace_callback(
            '/\{\{(.*?)\}\}/',
            function ($matches) use (&$params) {
                $params[] = trim($matches[1]);
                return '{{' . count($params) . '}}';
            },
            $content
        );

        return [
            'text' => $transformedText,
            'params' => $params
        ];
    }

    /**
     * Extract string content from potentially nested API data
     *
     * @param mixed $data
     * @return string|null
     */
    private function extractStringContent($data): ?string
    {
        if (is_array($data)) {
            if (isset($data['text'])) {
                return (string)$data['text'];
            }
            if (isset($data['componentData'])) {
                return $this->extractStringContent($data['componentData']);
            }
            if (isset($data[0])) {
                return $this->extractStringContent($data[0]);
            }
            return json_encode($data);
        }
        return $data !== null ? (string)$data : null;
    }
}
