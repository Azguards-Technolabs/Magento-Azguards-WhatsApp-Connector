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
                        $card['header_media_upload'] = [
                            [
                                'name' => basename($card['header_image']),
                                'url' => $card['header_image']
                            ]
                        ];
                    }
                }
            }
        }

        if (!empty($data['header_image'])) {
            $data['header_media_upload'] = [
                [
                    'name' => basename($data['header_image']),
                    'url' => $data['header_image']
                ]
            ];
        }

        return $data;
    }
}
