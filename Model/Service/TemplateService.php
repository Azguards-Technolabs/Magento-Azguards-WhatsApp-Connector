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

            // Execute actual API call
            $apiResponse = $this->templateApi->createTemplate($apiData);

            if (empty($apiResponse['template_id'])) {
                throw new LocalizedException(__('Failed to receive template ID from ERP.'));
            }

            $template = $this->templateFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $template,
                $data,
                \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
            );
            $template->setTemplateId($apiResponse['template_id']);

            $this->templateRepository->save($template);
            $this->logger->info("Template saved locally: " . $template->getId());

            return $template;
        } catch (\Exception $e) {
            $this->logger->error("Failed to create template: " . $e->getMessage());
            throw new LocalizedException(__('Failed to create template.'));
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

            // Merge existing data with new data
            $this->dataObjectHelper->populateWithArray(
                $template,
                $data,
                \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
            );

            $this->templateRepository->save($template);
            $this->logger->info("Template updated locally: " . $template->getId());

            return $template;
        } catch (\Exception $e) {
            $this->logger->error("Failed to update template: " . $e->getMessage());
            throw new LocalizedException(__('Failed to update template: ' . $e->getMessage()));
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
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete template: " . $e->getMessage());
            throw new LocalizedException(__('Failed to delete template: ' . $e->getMessage()));
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
     * Sync templates from API to local database
     *
     * @return array Summary of sync results
     */
    public function syncTemplates(): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0
        ];

        try {
            $apiTemplates = $this->templateApi->getTemplates();

            if (!isset($apiTemplates['result']['data']) || !is_array($apiTemplates['result']['data'])) {
                // Handle different response formats if necessary, based on ApiHelper::fetchTemplates
                if (isset($apiTemplates['data']) && is_array($apiTemplates['data'])) {
                    $templates = $apiTemplates['data'];
                } else {
                    $templates = $apiTemplates; // Assume it's the direct list if no standard wrapper found
                }
            } else {
                $templates = $apiTemplates['result']['data'];
            }

            $this->logger->info(sprintf("WhatsApp Sync: Found %d templates in API response.", count($templates)));

            // Debug: Log existing templates in DB
            $debugCollection = $this->templateRepository->getList($this->searchCriteriaBuilderFactory->create()->create());
            $this->logger->info(sprintf("WhatsApp Sync: Current total templates in DB: %d", $debugCollection->getTotalCount()));
            foreach ($debugCollection->getItems() as $debugItem) {
                $this->logger->info(sprintf(
                    "WhatsApp Sync: DB Record - ID: %s, Name: %s, Lang: %s, EntityID: %s",
                    $debugItem->getTemplateId(),
                    $debugItem->getTemplateName(),
                    $debugItem->getLanguage(),
                    $debugItem->getId()
                ));
            }

            foreach ($templates as $templateData) {
                try {
                    $templateId = $templateData['id'] ?? $templateData['template_id'] ?? null;
                    if (!$templateId) {
                        continue;
                    }

                    $mappedData = $this->mapApiResponseToLocal($templateData);
                    $this->logger->info(sprintf("WhatsApp Sync: Processing template %s. Mapped data: %s", (string)$templateId, json_encode($mappedData)));

                    $template = $this->getTemplateByExternalId(
                        (string)$templateId,
                        $mappedData['template_name'] ?? null,
                        $mappedData['language'] ?? null
                    );
                    $isNew = false;

                    if (!$template) {
                        $template = $this->templateFactory->create();
                        $isNew = true;
                    }

                    $this->dataObjectHelper->populateWithArray(
                        $template,
                        $mappedData,
                        \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
                    );

                    $this->templateRepository->save($template);

                    if ($isNew) {
                        $summary['created']++;
                        $this->logger->info(sprintf("WhatsApp Sync: Successfully created template %s", (string)$templateId));
                    } else {
                        $summary['updated']++;
                        $this->logger->info(sprintf("WhatsApp Sync: Successfully updated template %s", (string)$templateId));
                    }
                } catch (\Exception $e) {
                    $summary['errors']++;
                    $this->logger->error(sprintf(
                        "WhatsApp Sync: Error syncing template %s: %s. Stack trace: %s",
                        isset($templateId) ? (string)$templateId : 'unknown',
                        $e->getMessage(),
                        $e->getTraceAsString()
                    ));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync templates: " . $e->getMessage());
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

        if ($collection->getTotalCount() > 0) {
            $item = reset($collection->getItems());
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
                        if (isset($component['buttons']) && is_array($component['buttons']) && !empty($component['buttons'])) {
                            $button = $component['buttons'][0]; // Take the first button for now
                            $buttonType = isset($button['type']) ? strtolower($button['type']) : null;
                            $buttonText = $button['text'] ?? null;
                            $buttonUrl = $button['url'] ?? null;
                            $buttonPhone = $button['phoneNumber'] ?? null;
                        } elseif (is_array($component['componentData']) && !empty($component['componentData'])) {
                            // Alternative format sometimes seen in componentData
                            $button = $component['componentData'][0];
                            $buttonType = isset($button['type']) ? strtolower($button['type']) : null;
                            $buttonText = $button['text'] ?? null;
                            $buttonUrl = $button['url'] ?? null;
                            $buttonPhone = $button['phoneNumber'] ?? null;
                        }
                        break;
                }
            }
        }

        // Fallback to top-level fields if components are missing or empty
        $header = $header ?? $apiData['templateHeaderText'] ?? null;
        $body = $body ?: ($apiData['templateBodyText'] ?? '');
        $footer = $footer ?? $apiData['templateFooterText'] ?? null;

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
            'button_type' => $buttonType,
            'button_text' => $buttonText,
            'button_url' => $buttonUrl,
            'button_phone' => $buttonPhone
        ];
    }

    /**
     * Prepare API data
     *
     * @param array $data
     * @return array
     */
    private function prepareApiData(array $data): array
    {
        return [
            'name' => $data['template_name'] ?? '',
            'type' => $data['template_type'] ?? 'TEXT',
            'category' => $data['template_category'] ?? '',
            'language' => $data['language'] ?? 'en_US',
            'components' => [
                'header' => $data['header'] ?? null,
                'body' => $data['body'] ?? '',
                'footer' => $data['footer'] ?? null,
                'buttons' => [
                    'type' => $data['button_type'] ?? null,
                    'text' => $data['button_text'] ?? null,
                    'url' => $data['button_url'] ?? null,
                    'phone' => $data['button_phone'] ?? null
                ]
            ]
        ];
    }
}
