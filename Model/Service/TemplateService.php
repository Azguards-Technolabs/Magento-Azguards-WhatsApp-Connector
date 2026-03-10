<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Model\Api\TemplateApi;
use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterfaceFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;

class TemplateService
{
    private $templateApi;
    private $templateRepository;
    private $templateFactory;
    private $logger;
    private $dataObjectHelper;
    private $searchCriteriaBuilder;

    public function __construct(
        TemplateApi $templateApi,
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->templateApi = $templateApi;
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

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

            foreach ($templates as $templateData) {
                try {
                    $templateId = $templateData['id'] ?? $templateData['template_id'] ?? null;
                    if (!$templateId) {
                        continue;
                    }

                    $template = $this->getTemplateByExternalId((string)$templateId);
                    $isNew = false;

                    if (!$template) {
                        $template = $this->templateFactory->create();
                        $isNew = true;
                    }

                    $mappedData = $this->mapApiResponseToLocal($templateData);
                    $this->dataObjectHelper->populateWithArray(
                        $template,
                        $mappedData,
                        \Azguards\WhatsAppConnect\Api\Data\TemplateInterface::class
                    );

                    $this->templateRepository->save($template);

                    if ($isNew) {
                        $summary['created']++;
                    } else {
                        $summary['updated']++;
                    }
                } catch (\Exception $e) {
                    $summary['errors']++;
                    $this->logger->error("Error syncing individual template: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to sync templates: " . $e->getMessage());
            throw new LocalizedException(__('Failed to sync templates: %1', $e->getMessage()));
        }

        return $summary;
    }

    /**
     * Get template by its external (API) ID
     *
     * @param string $templateId
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface|null
     */
    private function getTemplateByExternalId(string $templateId): ?\Azguards\WhatsAppConnect\Api\Data\TemplateInterface
    {
        // Use collection to find by template_id
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('template_id', $templateId)
            ->create();

        $collection = $this->templateRepository->getList($searchCriteria);

        if ($collection->getTotalCount() > 0) {
            $items = $collection->getItems();
            return reset($items);
        }

        return null;
    }

    /**
     * Map API response fields to local TemplateInterface fields
     */
    private function mapApiResponseToLocal(array $apiData): array
    {
        return [
            'template_id' => $apiData['id'] ?? $apiData['template_id'] ?? '',
            'template_name' => $apiData['templateName'] ?? $apiData['name'] ?? '',
            'template_type' => $apiData['templateHeaderType'] ?? $apiData['type'] ?? 'TEXT',
            'template_category' => $apiData['templateCategory'] ?? $apiData['category'] ?? '',
            'language' => $apiData['language'] ?? 'en_US',
            'status' => $apiData['status'] ?? 'APPROVED',
            'header' => $apiData['templateHeaderText'] ?? $apiData['components']['header'] ?? null,
            'body' => $apiData['templateBodyText'] ?? $apiData['components']['body'] ?? '',
            'footer' => $apiData['templateFooterText'] ?? $apiData['components']['footer'] ?? null,
            'button_type' => $apiData['button_type'] ?? null,
            'button_text' => $apiData['button_text'] ?? null,
            'button_url' => $apiData['button_url'] ?? null,
            'button_phone' => $apiData['button_phone'] ?? null
        ];
    }

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
