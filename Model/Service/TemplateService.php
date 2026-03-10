<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Model\Api\TemplateApi;
use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterfaceFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\DataObjectHelper;

class TemplateService
{
    private $templateApi;
    private $templateRepository;
    private $templateFactory;
    private $logger;
    private $dataObjectHelper;

    public function __construct(
        TemplateApi $templateApi,
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->templateApi = $templateApi;
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
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
