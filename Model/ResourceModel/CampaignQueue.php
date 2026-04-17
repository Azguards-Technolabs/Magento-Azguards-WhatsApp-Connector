<?php

namespace Azguards\WhatsAppConnect\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CampaignQueue extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('azguards_whatsapp_campaign_queue', 'id');
    }
}
