<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel\TemplateComponent;

use Company\WhatsappTemplate\Model\ResourceModel\TemplateComponent as TemplateComponentResource;
use Company\WhatsappTemplate\Model\TemplateComponent as TemplateComponentModel;
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
        $this->_init(TemplateComponentModel::class, TemplateComponentResource::class);
    }
}
