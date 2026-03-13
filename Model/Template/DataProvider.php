<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Template;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;
use Magento\Backend\Model\Session;
use Magento\Framework\App\RequestInterface;
use Azguards\WhatsAppConnect\Model\Service\MediaDocumentService;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $loadedData;
    protected $session;
    protected $request;
    protected $collectionFactory;
    protected $mediaDocumentService;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Session $session,
        RequestInterface $request,
        MediaDocumentService $mediaDocumentService,
        array $meta = [],
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->collection = $collectionFactory->create();
        $this->session = $session;
        $this->request = $request;
        $this->mediaDocumentService = $mediaDocumentService;
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

                    $cardMediaValue = $card['header_image'] ?? null;
                    if (empty($cardMediaValue) && !empty($card['header_handle'])) {
                        $cardMediaValue = $this->resolvePreviewLink((string)$card['header_handle']);
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

        $headerMediaValue = $data['header_image'] ?? null;
        if (empty($headerMediaValue) && !empty($data['header_handle'])) {
            $headerMediaValue = $this->resolvePreviewLink((string)$data['header_handle']);
        }

        if (!empty($headerMediaValue) || !empty($data['header_handle'])) {
            $data['header_media_upload'] = $this->normalizeUploaderValue(
                $headerMediaValue,
                $data['header_format'] ?? null,
                $data['header_handle'] ?? null
            );
        }

        return $data;
    }

    private function normalizeUploaderValue($value, ?string $format = null, ?string $documentId = null): array
    {
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

    private function resolvePreviewLink(string $documentId): ?string
    {
        try {
            return $this->mediaDocumentService->getPreviewLink($documentId);
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
