<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Magento\Framework\Model\AbstractModel;

class ComponentVariable extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Company\WhatsappTemplate\Model\ResourceModel\ComponentVariable::class);
    }
}
