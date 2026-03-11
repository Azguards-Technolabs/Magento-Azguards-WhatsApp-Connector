<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TemplateComponent extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('whatsapp_template_components', 'id');
    }
}
