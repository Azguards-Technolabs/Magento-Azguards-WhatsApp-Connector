<?php

namespace Azguards\WhatsAppConnect\Model;

use Magento\Framework\Model\AbstractModel;

class CampaignQueue extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected function _construct()
    {
        $this->_init(\Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue::class);
    }
}
