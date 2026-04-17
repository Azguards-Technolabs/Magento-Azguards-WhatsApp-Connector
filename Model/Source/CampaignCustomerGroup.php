<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CampaignCustomerGroup implements OptionSourceInterface
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
        $collection->setOrder('customer_group_code', 'ASC');

        foreach ($collection as $group) {
            $options[] = [
                'value' => (int)$group->getId(),
                'label' => (string)$group->getCustomerGroupCode(),
            ];
        }

        return $options;
    }
}
