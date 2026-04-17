<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CampaignTemplateOptions implements OptionSourceInterface
{
    private CollectionFactory $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['entity_id', 'template_name', 'status']);
        $collection->addFieldToFilter('status', 'APPROVED');
        $collection->setOrder('template_name', 'ASC');

        foreach ($collection as $template) {
            $options[] = [
                'value' => (int)$template->getId(),
                'label' => (string)$template->getData('template_name'),
            ];
        }

        return $options;
    }
}
