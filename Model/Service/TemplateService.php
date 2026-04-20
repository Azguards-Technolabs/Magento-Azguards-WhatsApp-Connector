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
use Azguards\WhatsAppConnect\Model\Service\MetaTemplatePayloadBuilder;
use Azguards\WhatsAppConnect\Model\Service\MediaPersistenceService;
use Azguards\WhatsAppConnect\Model\Service\MediaDocumentService;
use Magento\Framework\Lock\LockManagerInterface;

class TemplateService
{
    private const SYNC_PAGE_SIZE = 100;
    private const SYNC_LOCK_NAME = 'azguards_whatsapp_template_sync';
    private const SYNC_LOCK_TIMEOUT = 0;

    private $templateApi;
    private $templateRepository;
    private $templateFactory;
    private $logger;
    private $dataObjectHelper;
    private $searchCriteriaBuilderFactory;
    private $payloadBuilder;
    private LockManagerInterface $lockManager;
    private MediaPersistenceService $mediaPersistence;
    private MediaDocumentService $mediaDocumentService;
    private MediaResolver $mediaResolver;

    public function __construct(
        TemplateApi $templateApi,
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        MetaTemplatePayloadBuilder $payloadBuilder,
        LockManagerInterface $lockManager,
        MediaPersistenceService $mediaPersistence,
        MediaDocumentService $mediaDocumentService,
        MediaResolver $mediaResolver
    ) {
        $this->templateApi = $templateApi;
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->payloadBuilder = $payloadBuilder;
        $this->lockManager = $lockManager;
        $this->mediaPersistence = $mediaPersistence;
        $this->mediaDocumentService = $mediaDocumentService;
        $this->mediaResolver = $mediaResolver;
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
            $template = $this->templateFactory->create();

            if (isset($data['entity_id'])) {
                unset($data['entity_id']);
            }

            $this->dataObjectHelper->populateWithArray(
                $template,
                $data,
                \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
            );

            $this->logger->info('TemplateService: Template populated before payload build', [
                'template_name' => $template->getTemplateName(),
                'template_type' => $template->getTemplateType(),
                'header_format' => $template->getHeaderFormat(),
                'header_handle' => $template->getHeaderHandle(),
                'header_image' => $template->getHeaderImage()
            ]);

            $apiData = $this->payloadBuilder->build($template);
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
                $errorMsg = $this->apiHelper->extractErrorMessage($apiResponse);
                throw new LocalizedException(__('ERP Response but no ID: %1', $errorMsg));
            }

            $template->setTemplateId($externalId);
            $template->setStatus('PENDING');

            // Senior Logic: Persist media assets locally after receiving ERP ID
            $this->persistTemplateMedia($template);

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

            $templateId = $template->getTemplateId();
            if (!$templateId) {
                throw new LocalizedException(__('Template ID is missing. Cannot update in ERP.'));
            }

            // Merge existing data with new data
            $this->dataObjectHelper->populateWithArray(
                $template,
                $data,
                \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
            );

            $this->logger->info('TemplateService: Template populated before update payload build', [
                'entity_id' => $entityId,
                'template_id' => $templateId,
                'template_name' => $template->getTemplateName(),
                'template_type' => $template->getTemplateType(),
                'header_format' => $template->getHeaderFormat(),
                'header_handle' => $template->getHeaderHandle(),
                'header_image' => $template->getHeaderImage()
            ]);

            $apiData = $this->payloadBuilder->build($template);
            $this->logger->info("ERP Update Template Payload: " . json_encode($apiData));

            // Execute actual API call
            $this->templateApi->updateTemplate($templateId, $apiData);
            $this->logger->info("Template updated in API successfully: " . $templateId);

            // Senior Logic: Refresh and persist media assets locally during update
            $this->persistTemplateMedia($template);

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
     * Sync all templates from the API across every available page.
     *
     * @return array ['created' => N, 'skipped' => N, 'errors' => N]
     */
    public function syncTemplates(): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => 0,
        ];

        $this->logger->info('WhatsApp Sync: Started (Full Sync).');
        $lockAcquired = false;

        try {
            $lockAcquired = $this->lockManager->lock(self::SYNC_LOCK_NAME, self::SYNC_LOCK_TIMEOUT);
            if (!$lockAcquired) {
                $this->logger->warning('WhatsApp Sync: Another sync is already in progress.');
                throw new LocalizedException(__('A template sync is already running. Please try again shortly.'));
            }

            $page = 0;
            $total = 0;
            $processed = 0;

            do {
                $result = $this->templateApi->getTemplatesPaginated($page, self::SYNC_PAGE_SIZE);
                $templates = $result['data'] ?? [];
                $total = max($total, (int)($result['total'] ?? 0));

                $this->logger->info(sprintf(
                    'WhatsApp Sync: Fetched page %d with %d templates (reported total: %d).',
                    $page,
                    count($templates),
                    $total
                ));

                if ($page === 1 && empty($templates)) {
                    $this->logger->info('WhatsApp Sync: No templates found, done.');
                    return $summary;
                }

                foreach ($templates as $templateData) {
                    try {
                        $templateId = $this->extractExternalTemplateId($templateData);
                        if (!$templateId) {
                            $summary['skipped']++;
                            $this->logger->warning('WhatsApp Sync: Skipped template without external ID.');
                            continue;
                        }

                        $existing = $this->getTemplateByExternalId(
                            $templateId,
                            $templateData['templateName'] ?? $templateData['name'] ?? null,
                            $templateData['languageCode'] ?? ($templateData['language']['code'] ?? ($templateData['language'] ?? null))
                        );
                        $template = $existing ?: $this->templateFactory->create();

                        $mappedData = $this->mapApiResponseToLocal($templateData);
                        $this->dataObjectHelper->populateWithArray(
                            $template,
                            $mappedData,
                            \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
                        );

                        // Senior Logic: Persist media assets locally during sync
                        $this->persistTemplateMedia($template);

                        $this->templateRepository->save($template);
                        $processed++;

                        if ($existing) {
                            $summary['updated']++;
                            $this->logger->info(sprintf('WhatsApp Sync: Updated template %s', (string)$templateId));
                        } else {
                            $summary['created']++;
                            $this->logger->info(sprintf('WhatsApp Sync: Created template %s', (string)$templateId));
                        }
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
                $hasMore = (bool)($result['hasMore'] ?? false);
            } while ($hasMore);

            $this->logger->info(sprintf(
                'WhatsApp Sync: Processed %d templates across %d page(s).',
                $processed,
                $page - 1
            ));

            if ($processed === 0) {
                $this->logger->info('WhatsApp Sync: No templates found, done.');
                return $summary;
            }

            $this->logger->info(sprintf(
                'WhatsApp Sync done - Created: %d, Updated: %d, Skipped: %d, Errors: %d, Total fetched: %d',
                $summary['created'],
                $summary['updated'],
                $summary['skipped'],
                $summary['errors'],
                $total
            ));

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Sync fatal: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to sync templates: %1', $e->getMessage()));
        } finally {
            if ($lockAcquired) {
                $this->lockManager->unlock(self::SYNC_LOCK_NAME);
            }
        }

        return $summary;
    }

    /**
     * Extract the external template identifier from any supported API shape.
     *
     * @param array $templateData
     * @return string|null
     */
    private function extractExternalTemplateId(array $templateData): ?string
    {
        $templateId = $templateData['id'] ?? $templateData['template_id'] ?? null;
        if ($templateId === null || $templateId === '') {
            return null;
        }

        return (string)$templateId;
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
    /**
     * Map API response to local data
     */
    private function mapApiResponseToLocal(array $apiData): array
    {
        $components = $apiData['components'] ?? [];
        $mappedComponents = $this->parseComponents($components);

        $category = $this->resolveCategory($apiData);
        $language = $this->resolveLanguage($apiData);

        $mappedData = [
            'template_id' => $apiData['id'] ?? $apiData['template_id'] ?? '',
            'template_name' => $apiData['templateName'] ?? $apiData['name'] ?? '',
            'template_type' => $mappedComponents['header_format'] ?: ($apiData['templateHeaderType'] ?? $apiData['type'] ?? 'TEXT'),
            'template_category' => $category,
            'language' => $language,
            'status' => $apiData['status'] ?? 'APPROVED',
            'header' => $mappedComponents['header'],
            'body' => $mappedComponents['body'],
            'footer' => $mappedComponents['footer'],
            'buttons' => $mappedComponents['buttons'],
            'body_examples_json' => $mappedComponents['body_examples_json']
        ];

        // Ensure template_type is correct for media headers
        if (in_array($mappedComponents['header_format'], ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $mappedData['template_type'] = $mappedComponents['header_format'];
        }

        if ($mappedComponents['header_format']) {
            $mappedData['header_format'] = $mappedComponents['header_format'];
        }

        if ($mappedComponents['header_image']) {
            $mappedData['header_image'] = $mappedComponents['header_image'];
        }

        if ($mappedComponents['header_handle']) {
            $mappedData['header_handle'] = $mappedComponents['header_handle'];
        }

        if (!empty($mappedComponents['carousel_cards'])) {
            $mappedData['carousel_cards'] = json_encode($mappedComponents['carousel_cards']);
            $mappedData['carousel_format'] = $mappedComponents['carousel_format'];
        }

        return $mappedData;
    }

    private function parseComponents(array $components): array
    {
        $data = [
            'header' => null,
            'body' => '',
            'footer' => null,
            'buttons' => null,
            'header_image' => null,
            'header_handle' => null,
            'header_format' => null,
            'body_examples_json' => null,
            'carousel_cards' => [],
            'carousel_format' => null
        ];

        foreach ($components as $component) {
            $type = strtoupper((string)($component['componentType'] ?? $component['type'] ?? ''));
            switch ($type) {
                case 'HEADER':
                    $this->parseHeaderComponent($component, $data);
                    break;
                case 'BODY':
                    $data['body'] = $this->extractStringContent($component['componentData'] ?? '');
                    if (isset($component['example']['body_text'][0])) {
                        $data['body_examples_json'] = json_encode($component['example']['body_text'][0]);
                    }
                    break;
                case 'FOOTER':
                    $data['footer'] = $this->extractStringContent($component['componentData'] ?? '');
                    break;
                case 'BUTTONS':
                    $data['buttons'] = $this->parseButtonsComponent($component);
                    break;
                case 'CAROUSEL':
                    $data['carousel_cards'] = $this->extractCarouselCards($component);
                    $data['carousel_format'] = $this->detectCarouselFormat($data['carousel_cards']);
                    break;
            }
        }

        return $data;
    }

    private function parseHeaderComponent(array $component, array &$data): void
    {
        $data['header_format'] = strtoupper((string)($component['componentFormat'] ?? $component['format'] ?? 'TEXT'));
        $media = $component['componentData'] ?? [];
        
        if ($data['header_format'] !== 'TEXT' && !empty($media)) {
            if (is_array($media)) {
                $innerMedia = $media['media'] ?? [];
                $data['header_image'] = $media['preview_link'] ?? $media['url'] ?? ($media['preview'] ?? null);
                if (!$data['header_image'] && is_array($innerMedia)) {
                    $data['header_image'] = $innerMedia['preview_link'] ?? $innerMedia['url'] ?? ($innerMedia['preview'] ?? null);
                }
                $data['header_handle'] = $this->mediaResolver->resolveHandler($media);
            } else {
                $data['header_image'] = $media;
                $data['header_handle'] = $this->mediaResolver->resolveHandler($media);
            }
        }

        if ($data['header_format'] === 'TEXT' || empty($data['header_format'])) {
            $data['header'] = $this->extractStringContent($component['componentData'] ?? null);
        }

        $data['header_image'] = $this->extractStringContent($data['header_image']);
        $data['header_handle'] = $this->extractStringContent($data['header_handle']);
    }

    private function parseButtonsComponent(array $component): ?string
    {
        $buttonsData = $component['buttons'] ?? $component['componentData'] ?? [];
        if (!is_array($buttonsData)) {
            return null;
        }

        $extracted = [];
        foreach ($buttonsData as $btn) {
            $extracted[] = [
                'type'  => strtolower($btn['type'] ?? ''),
                'text'  => $btn['text'] ?? '',
                'value' => $btn['url'] ?? $btn['phoneNumber'] ?? $btn['value'] ?? ''
            ];
        }

        return json_encode($extracted);
    }

    private function resolveCategory(array $apiData): string
    {
        $category = $apiData['categoryName'] ?? ($apiData['category']['name'] ?? ($apiData['templateCategory'] ?? ''));
        if (!$category) return 'MARKETING';

        $category = ucfirst(strtolower($category));
        return ($category === 'Auth') ? 'Authentication' : $category;
    }

    private function resolveLanguage(array $apiData): string
    {
        $language = $apiData['languageCode'] ?? ($apiData['language']['code'] ?? ($apiData['language'] ?? 'en_US'));
        return is_array($language) ? ($language['code'] ?? 'en_US') : $language;
    }

    private function extractCarouselCards(array $component): array
    {
        $cards = $component['cards']
            ?? $component['carouselCards']
            ?? $component['componentData']
            ?? [];

        if (!is_array($cards)) {
            return [];
        }

        $normalizedCards = [];
        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $normalizedCard = [
                'header_format' => strtoupper((string)($card['format'] ?? $card['header_format'] ?? 'IMAGE'))
            ];

            if (!empty($card['header']) && is_array($card['header'])) {
                $header = $card['header'];
                $media = $header['media'] ?? [];
                $normalizedCard['header_format'] = strtoupper((string)($header['format'] ?? $normalizedCard['header_format']));
                $normalizedCard['header_image'] = $header['componentData']
                    ?? ($media['preview_link'] ?? ($media['url'] ?? null));
                $normalizedCard['header_handle'] = $media['document_id'] ?? ($header['handle'] ?? null);
            } else {
                $normalizedCard['header_image'] = $card['header_image'] ?? ($card['image']['url'] ?? null);
                $normalizedCard['header_handle'] = $card['header_handle'] ?? ($card['image']['handle'] ?? null);
            }

            if (!empty($card['body'])) {
                $normalizedCard['body'] = is_array($card['body'])
                    ? (string)($card['body']['text'] ?? $card['body']['componentData'] ?? '')
                    : (string)$card['body'];
            } elseif (!empty($card['componentData'])) {
                $normalizedCard['body'] = $this->extractStringContent($card['componentData']) ?? '';
            }

            $buttons = $card['buttons'] ?? [];
            if (is_array($buttons) && !empty($buttons)) {
                $normalizedButtons = [];
                foreach ($buttons as $button) {
                    if (!is_array($button)) {
                        continue;
                    }

                    $normalizedButtons[] = [
                        'type' => strtolower((string)($button['type'] ?? '')),
                        'text' => (string)($button['text'] ?? ''),
                        'value' => (string)($button['url'] ?? $button['phoneNumber'] ?? $button['value'] ?? '')
                    ];
                }

                if (!empty($normalizedButtons)) {
                    $normalizedCard['buttons'] = $normalizedButtons;
                }
            }

            $normalizedCards[] = array_filter(
                $normalizedCard,
                static fn ($value) => $value !== null && $value !== ''
            );
        }

        return $normalizedCards;
    }

    private function detectCarouselFormat(array $cards): ?string
    {
        foreach ($cards as $card) {
            $format = strtoupper((string)($card['header_format'] ?? ''));
            if (in_array($format, ['IMAGE', 'VIDEO'], true)) {
                return $format;
            }
        }

        return null;
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
            if (isset($data['document_id'])) {
                return (string)$data['document_id'];
            }
            if (isset($data['handle'])) {
                return (string)$data['handle'];
            }
            if (isset($data[0]) && count($data) === 1 && !is_array($data[0])) {
                return (string)$data[0];
            }
            return json_encode($data);
        }
        return $data !== null ? (string)$data : null;
    }

    /**
     * Persist media assets associated with a template.
     */
    private function persistTemplateMedia(\Azguards\WhatsAppConnect\Api\Data\TemplateInterface $template): void
    {
        $headerFormat = strtoupper((string)$template->getHeaderFormat());
        $templateId = (string)($template->getTemplateId() ?: $template->getTemplateName());

        // 1. Persist Header Image/Video/Document
        if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $handleRaw = $template->getHeaderHandle();
            $handle = $this->mediaResolver->resolveHandler($handleRaw);
            $url = null;

            if ($handle) {
                try {
                    // Try to get a high-quality fresh preview URL from the API
                    $url = $this->mediaDocumentService->getPreviewLink((string)$handle, false);
                } catch (\Throwable $e) {
                    $this->logger->warning("Sync: Failed to resolve handle $handle for $templateId");
                }
            }

            // Fallback: If no URL from handle, but header_image is a URL, use it
            if (!$url) {
                $currentImage = $template->getHeaderImage();
                if ($currentImage && filter_var($currentImage, FILTER_VALIDATE_URL)) {
                    $url = $currentImage;
                }
            }

            if ($url) {
                try {
                    $localPath = $this->mediaPersistence->persistFromUrl($url, $templateId . '_header');
                    if ($localPath) {
                        $template->setHeaderImage($localPath);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error("Sync: Failed to persist header media for $templateId: " . $e->getMessage());
                }
            }
        }

        // 2. Persist Carousel Card Images
        $cardsJson = $template->getCarouselCards();
        if ($cardsJson) {
            $cards = json_decode((string)$cardsJson, true);
            if (is_array($cards)) {
                $changed = false;
                foreach ($cards as $i => &$card) {
                    $handleRaw = $card['header_handle'] ?? null;
                    $cardHeaderFormat = strtoupper((string)($card['header_format'] ?? 'IMAGE'));
                    
                    if ($cardHeaderFormat !== 'TEXT' && $handleRaw) {
                        $handle = $this->mediaResolver->resolveHandler($handleRaw);
                        $url = null;

                        if ($handle) {
                            try {
                                $url = $this->mediaDocumentService->getPreviewLink((string)$handle, false);
                            } catch (\Throwable $e) {
                                // Silently continue for carousel cards
                            }
                        }
                    }

                    // Fallback to existing image URL if handle fails
                    if (!$url) {
                        $currentImage = $card['header_image'] ?? null;
                        if ($currentImage && filter_var($currentImage, FILTER_VALIDATE_URL)) {
                            $url = $currentImage;
                        }
                    }

                    if ($url) {
                        try {
                            $localPath = $this->mediaPersistence->persistFromUrl($url, $templateId . '_card_' . $i);
                            if ($localPath) {
                                $card['header_image'] = $localPath;
                                $changed = true;
                            }
                        } catch (\Throwable $e) {
                            // Continue to next card
                        }
                    }
                }
                if ($changed) {
                    $template->setCarouselCards(json_encode($cards));
                }
            }
        }
    }
}
