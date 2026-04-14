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

class TemplateService
{
    private const SYNC_PAGE_SIZE = 100;

    private $templateApi;
    private $templateRepository;
    private $templateFactory;
    private $logger;
    private $dataObjectHelper;
    private $searchCriteriaBuilderFactory;
    private $payloadBuilder;

    public function __construct(
        TemplateApi $templateApi,
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        MetaTemplatePayloadBuilder $payloadBuilder
    ) {
        $this->templateApi = $templateApi;
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->payloadBuilder = $payloadBuilder;
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
                $errorMsg = $apiResponse['message'] ?? $apiResponse['error']['message'] ?? 'Unknown ERP Error';
                throw new LocalizedException(__('ERP Response but no ID: %1', $errorMsg));
            }

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

        try {
            $page = 1;
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
                        $templateId = $templateData['id'] ?? $templateData['template_id'] ?? null;
                        if (!$templateId) {
                            $summary['skipped']++;
                            $this->logger->warning('WhatsApp Sync: Skipped template without external ID.');
                            continue;
                        }

                        $existing = $this->getTemplateByExternalId((string)$templateId);
                        $template = $existing ?: $this->templateFactory->create();

                        $mappedData = $this->mapApiResponseToLocal($templateData);
                        $this->dataObjectHelper->populateWithArray(
                            $template,
                            $mappedData,
                            \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
                        );

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
        $headerImage = null;
        $headerHandle = null;
        $headerFormat = null;
        $carouselCards = [];
        $carouselFormat = null;

        if (isset($apiData['components']) && is_array($apiData['components'])) {
            foreach ($apiData['components'] as $component) {
                $componentType = strtoupper((string)($component['componentType'] ?? $component['type'] ?? ''));

                switch ($componentType) {
                    case 'HEADER':
                        $headerFormat = strtoupper((string)($component['format'] ?? 'TEXT'));
                        if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                            $media = $component['media'] ?? [];
                            $headerImage = $component['componentData']
                                ?? ($media['preview_link'] ?? ($media['url'] ?? ($component['image']['url'] ?? null)));
                            $headerHandle = $media['document_id']
                                ?? ($component['image']['handle'] ?? ($component['document']['handle'] ?? null));
                        } else {
                            $header = $component['componentData'] ?? null;
                        }
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
                    case 'CAROUSEL':
                        $carouselCards = $this->extractCarouselCards($component);
                        $carouselFormat = $this->detectCarouselFormat($carouselCards);
                        break;
                }
            }
        }

        $header = $this->extractStringContent($header ?? ($apiData['templateHeaderText'] ?? null));
        $body = $this->extractStringContent($body ?: ($apiData['templateBodyText'] ?? '')) ?: '';
        $footer = $this->extractStringContent($footer ?? ($apiData['templateFooterText'] ?? null));

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

        $language = $apiData['languageCode'] ?? '';
        if (empty($language) && isset($apiData['language']['code'])) {
            $language = $apiData['language']['code'];
        } elseif (empty($language) && isset($apiData['language'])) {
            $language = is_array($apiData['language']) ? ($apiData['language']['code'] ?? 'en_US') : $apiData['language'];
        }

        if ($buttonType) {
            $buttonType = strtolower($buttonType);
        }

        $mappedData = [
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

        $resolvedHeaderImage = $this->extractStringContent($headerImage);
        $resolvedHeaderHandle = $this->extractStringContent($headerHandle);

        if (!empty($headerFormat)) {
            $mappedData['header_format'] = $headerFormat;
        }

        if (!empty($resolvedHeaderImage)) {
            $mappedData['header_image'] = $resolvedHeaderImage;
        }

        if (!empty($resolvedHeaderHandle)) {
            $mappedData['header_handle'] = $resolvedHeaderHandle;
        }

        if (!empty($carouselCards)) {
            $mappedData['carousel_cards'] = json_encode($carouselCards);
            $mappedData['carousel_format'] = $carouselFormat ?: $this->detectCarouselFormat($carouselCards);
        }

        return $mappedData;
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
            if (isset($data[0])) {
                return $this->extractStringContent($data[0]);
            }
            return json_encode($data);
        }
        return $data !== null ? (string)$data : null;
    }
}
