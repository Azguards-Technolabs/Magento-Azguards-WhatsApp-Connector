<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\ResourceModel\Campaign;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Azguards\WhatsAppConnect\Model\Campaign as CampaignModel;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign as CampaignResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(CampaignModel::class, CampaignResource::class);
    }
}
