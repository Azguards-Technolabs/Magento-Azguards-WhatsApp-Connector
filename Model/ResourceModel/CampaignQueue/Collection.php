<?php

namespace Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize the campaign queue collection model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Azguards\WhatsAppConnect\Model\CampaignQueue::class,
            \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue::class
        );
    }
}
