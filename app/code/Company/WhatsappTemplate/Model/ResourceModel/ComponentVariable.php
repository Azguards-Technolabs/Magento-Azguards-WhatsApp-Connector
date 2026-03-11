<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ComponentVariable extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('whatsapp_component_variables', 'id');
    }
}
