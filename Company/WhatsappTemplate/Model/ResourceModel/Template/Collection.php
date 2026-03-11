<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel\Template;

use Company\WhatsappTemplate\Model\ResourceModel\Template as TemplateResource;
use Company\WhatsappTemplate\Model\Template as TemplateModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(TemplateModel::class, TemplateResource::class);
    }
}
