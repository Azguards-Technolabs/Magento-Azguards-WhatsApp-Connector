<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Template;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta()
    {
        $meta = parent::getMeta();

        // Check if editing (id parameter present in request, but DataProvider context doesn't have it easily without Request object)
        // Since we load data based on id, let's disable template_type if we have loaded items.
        // A better way is checking in `getMeta` loop or simply in Controller using modifier,
        // but let's disable it dynamically in `getData` or via UI Component XML if needed. We can do it here by overriding getMeta if id is present.

        return $meta;
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $template) {
            $data = $template->getData();
            $this->loadedData[$template->getId()] = $data;
        }

        if (!empty($this->loadedData)) {
             // Disable template type if editing
            $this->meta['general']['children']['template_type']['arguments']['data']['config']['disabled'] = true;
        }

        return $this->loadedData;
    }
}
