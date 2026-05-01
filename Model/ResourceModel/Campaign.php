<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Campaign extends AbstractDb
{
    /**
     * Initialize the campaign resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('azguards_whatsapp_campaigns', 'entity_id');
    }
}
