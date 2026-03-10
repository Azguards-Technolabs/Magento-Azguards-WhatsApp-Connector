<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Template extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('azguards_whatsapp_templates', 'entity_id');
    }
}
