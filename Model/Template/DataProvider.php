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
            if (!empty($data['buttons']) && is_string($data['buttons'])) {
                $data['buttons'] = json_decode($data['buttons'], true);
            }
            $this->loadedData[$template->getId()] = $data;
        }

        // Recover data from session if available (set in Save controller on error)
        $data = $this->session->getFormData(true);
        if (!empty($data)) {
            if (!empty($data['buttons']) && is_string($data['buttons'])) {
                $data['buttons'] = json_decode($data['buttons'], true);
            }
            $id = isset($data['entity_id']) ? $data['entity_id'] : null;
            $this->loadedData[$id] = $data;
        }

        if (!empty($this->loadedData)) {
             // Disable template type if editing
            $this->meta['general']['children']['template_type']['arguments']['data']['config']['disabled'] = true;
        }

        return $this->loadedData;
    }
}
