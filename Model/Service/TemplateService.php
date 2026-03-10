<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterfaceFactory;
use Azguards\WhatsAppConnect\Model\Service\Validator;
use Azguards\WhatsAppConnect\Model\Service\MetaServiceAdapter;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Webapi\Exception as WebapiException;

class TemplateService
{
    private $templateRepository;
    private $templateFactory;
    private $validator;
    private $metaAdapter;
    private $logger;
    private $random;
    private $json;
    private $searchCriteriaBuilder;
    private $filterBuilder;

    public function __construct(
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        Validator $validator,
        MetaServiceAdapter $metaAdapter,
        LoggerInterface $logger,
        Random $random,
        Json $json,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->validator = $validator;
        $this->metaAdapter = $metaAdapter;
        $this->logger = $logger;
        $this->random = $random;
        $this->json = $json;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    public function createTemplate(array $data): \Azguards\WhatsAppConnect\Api\Data\TemplateInterface
    {
        try {
            // 1. Validate
            $this->validator->validate($data);

            // 2. Duplicate Check
            $this->checkDuplicate($data['name'], $data['language']);

            // 3. Fetch Library Template if present
            if (!empty($data['libraryTemplateId'])) {
                $libraryData = $this->metaAdapter->fetchLibraryTemplate($data['libraryTemplateId']);
                $data['components'] = $this->mapLibraryComponents($libraryData);
            }

            // 4. Handle Media
            $data['components'] = $this->handleMedia($data['components']);

            // 5. Sanitize and Prepare Meta Payload
            $metaPayload = $this->prepareMetaPayload($data);

            // 6. Create in Meta
            $metaResponse = $this->metaAdapter->createTemplate($metaPayload);

            // 7. Save Locally
            $template = $this->templateFactory->create();
            $template->setUuid($this->random->getUniqueHash());
            $template->setTemplateName($data['name']);
            $template->setTemplateType($data['type']);
            $template->setTemplateCategory($data['category']);
            $template->setLanguage($data['language']);
            $template->setStatus('PENDING');
            $template->setMetaTemplateId($metaResponse['id'] ?? null);
            $template->setComponents($this->json->serialize($data['components']));

            $this->templateRepository->save($template);

            return $template;
        } catch (\Exception $e) {
            $this->logger->error("Template creation failed: " . $e->getMessage());
            if ($e->getCode() === 409) {
                throw new WebapiException(__($e->getMessage()), 0, 409);
            }
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    private function checkDuplicate(string $name, string $language): void
    {
        $this->searchCriteriaBuilder->addFilter('template_name', $name);
        $this->searchCriteriaBuilder->addFilter('language', $language);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $list = $this->templateRepository->getList($searchCriteria);

        if ($list->getTotalCount() > 0) {
            throw new \Exception(__('A template with name "%1" and language "%2" already exists.', $name, $language), 409);
        }
    }

    private function handleMedia(array $components): array
    {
        foreach ($components as &$component) {
            if ($component['type'] === 'HEADER' && in_array($component['format'], ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                if (!empty($component['media_data'])) {
                    $component['header_handle'] = $this->metaAdapter->fetchMediaHeaderHandle($component['media_data']);
                }
            }
            if ($component['type'] === 'CAROUSEL') {
                foreach ($component['cards'] as &$card) {
                    foreach ($card['components'] as &$cardComponent) {
                        if ($cardComponent['type'] === 'HEADER' && !empty($cardComponent['media_data'])) {
                            $cardComponent['header_handle'] = $this->metaAdapter->fetchMediaHeaderHandle($cardComponent['media_data']);
                        }
                    }
                }
            }
        }
        return $components;
    }

    private function prepareMetaPayload(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'category' => $data['category'],
            'language' => $data['language'],
            'components' => $this->sanitizeComponents($data['components'])
        ];

        if (isset($data['message_send_ttl_seconds'])) {
            $payload['message_send_ttl_seconds'] = $data['message_send_ttl_seconds'];
        }

        return $payload;
    }

    private function sanitizeComponents(array $components): array
    {
        foreach ($components as &$component) {
            // Remove frontend only fields
            unset($component['media_data']);

            if ($component['type'] === 'BUTTONS') {
                foreach ($component['buttons'] as &$button) {
                    if ($button['type'] === 'COPY_CODE') {
                        unset($button['text']); // Meta doesn't accept text for COPY_CODE
                    }
                }
            }
        }
        return $components;
    }

    private function mapLibraryComponents(array $libraryData): array
    {
        return $libraryData['components'] ?? [];
    }
}
