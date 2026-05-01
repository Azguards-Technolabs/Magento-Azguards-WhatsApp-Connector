<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\ResourceModel\Template;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Azguards\WhatsAppConnect\Model\Template as TemplateModel;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize the template collection model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(TemplateModel::class, TemplateResource::class);
    }
}
