<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Template;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;
use Magento\Backend\Model\Session;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $loadedData;
    protected $session;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Session $session,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->session = $session;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $template) {
            $data = $template->getData();
            $data = $this->decodeJsonFields($data);
            $this->loadedData[$template->getId()] = $data;
        }

        // Recover data from session if available (set in Save controller on error)
        $sessionData = $this->session->getFormData(true);
        if (!empty($sessionData)) {
            $sessionData = $this->decodeJsonFields($sessionData);
            $id = isset($sessionData['entity_id']) ? $sessionData['entity_id'] : null;
            $this->loadedData[$id] = $sessionData;
        }

        if (!empty($this->loadedData)) {
             // Disable template type if editing
            $this->meta['general']['children']['template_type']['arguments']['data']['config']['disabled'] = true;
        }

        return $this->loadedData;
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
                    if (!empty($card['header_image'])) {
                        $normalizedMedia = $this->normalizeUploaderValue(
                            $card['header_image'],
                            $card['header_format'] ?? null
                        );
                        if (!empty($normalizedMedia)) {
                            $card['header_media_upload'] = $normalizedMedia;
                        }
                    }
                }
            }
        }

        if (!empty($data['header_image'])) {
            $data['header_media_upload'] = $this->normalizeUploaderValue(
                $data['header_image'],
                $data['header_format'] ?? null
            );
        }

        return $data;
    }

    private function normalizeUploaderValue($value, ?string $format = null): array
    {
        if (empty($value)) {
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
                    'url' => is_string($url) ? $url : $file
                ], $format)];
            }

            return [];
        }

        if (is_string($value)) {
            return [$this->prepareUploaderFile([
                'name' => basename((string)parse_url($value, PHP_URL_PATH)),
                'url' => $value
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
