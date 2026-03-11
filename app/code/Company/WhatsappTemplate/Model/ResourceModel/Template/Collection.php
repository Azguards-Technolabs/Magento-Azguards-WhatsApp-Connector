<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel\Template;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Company\WhatsappTemplate\Model\Template::class,
            \Company\WhatsappTemplate\Model\ResourceModel\Template::class
        );
    }
}
