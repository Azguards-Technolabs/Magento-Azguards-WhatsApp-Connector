<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Template;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;
use Magento\Backend\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Azguards\WhatsAppConnect\Model\Service\MediaDocumentService;
use Azguards\WhatsAppConnect\Model\Service\MediaResolver;
use Azguards\WhatsAppConnect\Model\Service\MediaPersistenceService;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableExtractor;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableRowsBuilder;
use Azguards\WhatsAppConnect\Model\Service\VariableOptionsProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $loadedData;
    protected $session;
    protected $request;
    protected $collectionFactory;
    private $mediaDocumentService;
    private $mediaResolver;
    private $mediaPersistence;
    private StoreManagerInterface $storeManager;
    private Filesystem $filesystem;
    private ScopeConfigInterface $scopeConfig;
    private EventConfig $eventConfig;
    private TemplateVariableExtractor $variableExtractor;
    private VariableOptionsProvider $variableOptionsProvider;
    private TemplateVariableRowsBuilder $variableRowsBuilder;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Session $session,
        RequestInterface $request,
        MediaDocumentService $mediaDocumentService,
        MediaResolver $mediaResolver,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        MediaPersistenceService $mediaPersistence,
        ScopeConfigInterface $scopeConfig,
        EventConfig $eventConfig,
        TemplateVariableExtractor $variableExtractor,
        VariableOptionsProvider $variableOptionsProvider,
        TemplateVariableRowsBuilder $variableRowsBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->collectionFactory = $collectionFactory;
        $this->session = $session;
        $this->request = $request;
        $this->mediaDocumentService = $mediaDocumentService;
        $this->mediaResolver = $mediaResolver;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->mediaPersistence = $mediaPersistence;
        $this->scopeConfig = $scopeConfig;
        $this->eventConfig = $eventConfig;
        $this->variableExtractor = $variableExtractor;
        $this->variableOptionsProvider = $variableOptionsProvider;
        $this->variableRowsBuilder = $variableRowsBuilder;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $requestId = $this->request->getParam($this->requestFieldName);
        $items = $this->getRequestedItems($requestId);

        foreach ($items as $template) {
            $data = $template->getData();
            $data = $this->decodeJsonFields($data);

            $this->loadedData[$template->getId()] = $data;

            if ($requestId && $requestId != $template->getId()) {
                $this->loadedData[$requestId] = $data;
            }
        }

        // Recover data from session if available (set in Save controller on error)
        $sessionData = $this->session->getFormData(true);
        if (!empty($sessionData)) {
            $sessionData = $this->decodeJsonFields($sessionData);
            $id = isset($sessionData['entity_id']) ? $sessionData['entity_id'] : null;
            $this->loadedData[$id] = $sessionData;
        }

        if ($requestId || (!empty($sessionData['entity_id']) && !empty($this->loadedData))) {
            // Template type must remain immutable for an existing template.
            $this->meta['general']['children']['template_type']['arguments']['data']['config']['disabled'] = true;
        }

        return $this->loadedData;
    }

    private function formatDetectedVariables($template, array $variables): string
    {
        if ($variables === []) {
            return '';
        }

        $hasNumeric = false;
        foreach ($variables as $v) {
            if (is_numeric((string)$v)) {
                $hasNumeric = true;
                break;
            }
        }

        if (!$hasNumeric) {
            return implode(', ', $variables);
        }

        $externalTemplateId = (string)$template->getData('template_id');
        $rows = $this->variableRowsBuilder->buildByExternalTemplateId($externalTemplateId);
        if (empty($rows)) {
            return implode(', ', $variables);
        }

        $posToName = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pos = (string)($row['order'] ?? '');
            $name = (string)($row['type'] ?? $row['title'] ?? '');
            if ($pos !== '' && $name !== '') {
                $posToName[$pos] = $name;
            }
        }

        $out = [];
        foreach ($variables as $v) {
            $v = (string)$v;
            $out[] = (is_numeric($v) && isset($posToName[$v])) ? $posToName[$v] : $v;
        }

        return implode(', ', $out);
    }

    private function buildConfigMappingSummary($template, array $variables): string
    {
        if (!$template || !$template->getId() || $variables === []) {
            return '';
        }

        foreach ($this->getKnownEventCodes() as $eventCode) {
            $cfg = $this->eventConfig->get($eventCode);
            if ($cfg === [] || empty($cfg['template']) || empty($cfg['variables'])) {
                continue;
            }

            $configuredTemplateId = (string)$this->scopeConfig->getValue((string)$cfg['template']);
            if ($configuredTemplateId === '' || $configuredTemplateId !== (string)$template->getData('template_id')) {
                continue;
            }

            $raw = (string)$this->scopeConfig->getValue((string)$cfg['variables']);
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                return sprintf('Configured for %s, but variable mapping is empty.', $eventCode);
            }

            $options = $this->variableOptionsProvider->getForEvent($eventCode);
            $lines = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $varName = (string)($row['type'] ?? $row['title'] ?? '');
                $source = trim((string)($row['limit'] ?? ''));
                if ($varName === '' || $source === '') {
                    continue;
                }
                $label = $options[$source] ?? $source;
                $lines[] = sprintf('%s -> %s (%s)', $varName, $label, $source);
            }

            if ($lines === []) {
                return sprintf('Configured for %s, but no mapped variables found.', $eventCode);
            }

            return "Configured for {$eventCode}:\n" . implode("\n", $lines);
        }

        return '';
    }

    private function getKnownEventCodes(): array
    {
        return [
            EventConfig::CUSTOMER_REGISTRATION,
            EventConfig::ORDER_CREATION,
            EventConfig::ORDER_INVOICE,
            EventConfig::ORDER_SHIPMENT,
            EventConfig::ORDER_CANCELLATION,
            EventConfig::ORDER_CREDIT_MEMO,
            EventConfig::ABANDON_CART,
        ];
    }

    private function getRequestedItems($requestId): array
    {
        if (!$requestId) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('entity_id', $requestId);
        $items = $collection->getItems();

        if (!empty($items)) {
            return $items;
        }

        $fallbackCollection = $this->collectionFactory->create();
        $fallbackCollection->addFieldToFilter('template_id', $requestId);

        return $fallbackCollection->getItems();
    }

    private function decodeJsonFields(array $data): array
    {
        if (!empty($data['buttons']) && is_string($data['buttons'])) {
            $data['buttons'] = json_decode($data['buttons'], true);
        }

        if (!empty($data['body_examples_json']) && is_string($data['body_examples_json'])) {
            $decoded = json_decode($data['body_examples_json'], true);
            if (is_array($decoded)) {
                $examples = [];
                foreach ($decoded as $val) {
                    $examples[] = ['example' => $val];
                }
                $data['body_examples'] = $examples;
            }
        }

        if (!empty($data['carousel_cards']) && is_string($data['carousel_cards'])) {
            $data['carousel_cards'] = json_decode($data['carousel_cards'], true);
            if (is_array($data['carousel_cards'])) {
                foreach ($data['carousel_cards'] as &$card) {
                    if (!empty($card['buttons_json']) && is_string($card['buttons_json'])) {
                        $decodedButtons = json_decode($card['buttons_json'], true);
                        if (is_array($decodedButtons)) {
                            $card['buttons'] = $decodedButtons;
                        }
                    }

                    // Extract handle robustly for carousel cards
                    $card['header_handle'] = $this->mediaResolver->resolveHandler($card['header_handle'] ?? ($card['header'] ?? null));

                    $cardMediaValue = $card['header_image'] ?? null;
                    if (empty($cardMediaValue) && !empty($card['header_handle'])) {
                        $cardMediaValue = $this->resolvePreviewLink((string)$card['header_handle']);
                    }

                    // Senior Logic: If we have a URL (even if already set), try to persist it locally
                    if ($cardMediaValue && filter_var($cardMediaValue, FILTER_VALIDATE_URL)) {
                        $templateId = (string)($data['template_id'] ?? $data['entity_id'] ?? uniqid());
                        $localPath = $this->mediaPersistence->persistFromUrl($cardMediaValue, $templateId . '_card_' . $i);
                        if ($localPath) {
                            $cardMediaValue = $localPath;
                            // Note: We don't save back to DB here as this is DataProvider, but next save will persist it
                        }
                    }

                    if (!empty($cardMediaValue) || !empty($card['header_handle'])) {
                        $normalizedMedia = $this->normalizeUploaderValue(
                            $cardMediaValue,
                            $card['header_format'] ?? null,
                            $card['header_handle'] ?? null
                        );
                        if (!empty($normalizedMedia)) {
                            $card['header_media_upload'] = $normalizedMedia;
                        }
                    }
                }
            }
        }

        // Senior Media Resolution: Robustly extract handler from possibly nested/JSON data
        // This handles the new sync format where document_id is nested inside 'media'
        $data['header_handle'] = $this->mediaResolver->resolveHandler($data['header_handle'] ?? ($data['header'] ?? null));

        // Clear raw JSON if it was stored in the text 'header' field
        if (!empty($data['header']) && str_starts_with(trim((string)$data['header']), '{')) {
            $data['header'] = '';
        }

        $headerMediaValue = $data['header_image'] ?? null;
        if (empty($headerMediaValue) && !empty($data['header_handle'])) {
            $headerMediaValue = $this->resolvePreviewLink((string)$data['header_handle']);
        }

        // AGGRESSIVE PERSISTENCE: If it's still a remote URL, persist it locally
        if ($headerMediaValue && filter_var($headerMediaValue, FILTER_VALIDATE_URL)) {
            $templateId = (string)($data['template_id'] ?? $data['entity_id'] ?? uniqid());
            $localPath = $this->mediaPersistence->persistFromUrl($headerMediaValue, $templateId . '_header');
            if ($localPath) {
                $headerMediaValue = $localPath;
            }
        }

        if (!empty($headerMediaValue) || !empty($data['header_handle'])) {
            $data['header_media_upload'] = $this->normalizeUploaderValue(
                $headerMediaValue,
                $data['header_format'] ?? null,
                $data['header_handle'] ?? null
            );
        }

        // Admin UX: Show named variables in editor even if stored as numeric placeholders ({{1}}, {{2}}).
        $this->mapNumericPlaceholdersForDisplay($data);

        return $data;
    }

    private function mapNumericPlaceholdersForDisplay(array &$data): void
    {
        $externalTemplateId = (string)($data['template_id'] ?? '');
        if ($externalTemplateId === '') {
            return;
        }

        $rows = $this->variableRowsBuilder->buildByExternalTemplateId($externalTemplateId);
        if (empty($rows)) {
            return;
        }

        $positionToName = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pos = (string)($row['order'] ?? '');
            $name = (string)($row['type'] ?? $row['title'] ?? '');
            if ($pos !== '' && $name !== '') {
                $positionToName[$pos] = $name;
            }
        }

        if ($positionToName === []) {
            return;
        }

        foreach (['header', 'body', 'footer'] as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                $data[$field] = $this->replaceNumericPlaceholders($data[$field], $positionToName);
            }
        }

        if (isset($data['carousel_cards']) && is_array($data['carousel_cards'])) {
            foreach ($data['carousel_cards'] as &$card) {
                if (!is_array($card)) {
                    continue;
                }
                foreach (['header', 'body', 'footer'] as $field) {
                    if (isset($card[$field]) && is_string($card[$field]) && $card[$field] !== '') {
                        $card[$field] = $this->replaceNumericPlaceholders($card[$field], $positionToName);
                    }
                }
            }
            unset($card);
        }
    }

    private function replaceNumericPlaceholders(string $text, array $positionToName): string
    {
        if ($text === '' || $positionToName === []) {
            return $text;
        }

        return (string)preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function (array $m) use ($positionToName) {
            $pos = (string)($m[1] ?? '');
            if ($pos !== '' && isset($positionToName[$pos])) {
                return '{{' . $positionToName[$pos] . '}}';
            }
            return $m[0] ?? '';
        }, $text);
    }

    private function normalizeUploaderValue($value, ?string $format = null, ?string $documentId = null): array
    {
        $format = $format ?: 'IMAGE';
        $handle = $documentId;

        // If it's a local path, resolve to URL
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            $value = $this->resolveMediaUrl($value);
        }

        if (empty($value)) {
            if ($documentId) {
                return [$this->prepareUploaderFile([
                    'name' => $documentId,
                    'url' => '',
                    'document_id' => $documentId,
                    'preview_link' => ''
                ], $format)];
            }

            return [];
        }

        // Already normalized uploader data.
        if (is_array($value) && isset($value[0]) && is_array($value[0])) {
            return array_map(function (array $file) use ($format) {
                return $this->prepareUploaderFile($file, $format);
            }, $value);
        }

        // Single file array coming from session or partial uploader state.
        if (is_array($value)) {
            $file = $value['file'] ?? $value['name'] ?? $value['url'] ?? null;
            $url = $value['url'] ?? null;

            if (is_string($file)) {
                return [$this->prepareUploaderFile([
                    'name' => basename($file),
                    'url' => is_string($url) ? $url : $file,
                    'document_id' => $value['document_id'] ?? $documentId,
                    'preview_link' => $value['preview_link'] ?? $url
                ], $format)];
            }

            return [];
        }

        if (is_string($value)) {
            return [$this->prepareUploaderFile([
                'name' => basename((string)parse_url($value, PHP_URL_PATH)),
                'url' => $value,
                'document_id' => $documentId,
                'preview_link' => $value
            ], $format)];
        }

        return [];
    }

    private function prepareUploaderFile(array $file, ?string $format = null): array
    {
        $url = (string)($file['url'] ?? '');
        $name = (string)($file['name'] ?? basename((string)parse_url($url, PHP_URL_PATH)));

        $file['name'] = $name ?: 'media';
        $file['url'] = $url;
        $file['size'] = isset($file['size']) && is_numeric($file['size']) ? (float)$file['size'] : 0;
        $file['type'] = (string)($file['type'] ?? $this->resolveMimeType($format));

        return $file;
    }

    /**
     * Resolve a relative media path or URL to a full URL.
     */
    private function resolveMediaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        try {
            return rtrim($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/') . '/' . ltrim($path, '/');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function resolvePreviewLink(string $documentId): ?string
    {
        try {
            // Use fast mode (retry=false) for synchronous preview rendering to avoid blocking
            $resolved = $this->mediaDocumentService->getPreviewLink($documentId, false);
            return ($resolved && filter_var($resolved, FILTER_VALIDATE_URL)) ? $resolved : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveMimeType(?string $format): string
    {
        return match (strtoupper((string)$format)) {
            'IMAGE' => 'image/png',
            'VIDEO' => 'video/mp4',
            'DOCUMENT' => 'application/pdf',
            default => 'application/octet-stream'
        };
    }
}
