<?php
namespace Azguards\WhatsappConnector\Model\ResourceModel\Template;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'template_id';

    protected function _construct()
    {
        $this->_init(
            \Azguards\WhatsappConnector\Model\Template::class,
            \Azguards\WhatsappConnector\Model\ResourceModel\Template::class
        );
    }
}
