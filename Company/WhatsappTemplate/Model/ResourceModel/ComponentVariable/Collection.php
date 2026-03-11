<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel\ComponentVariable;

use Company\WhatsappTemplate\Model\ResourceModel\ComponentVariable as ComponentVariableResource;
use Company\WhatsappTemplate\Model\ComponentVariable as ComponentVariableModel;
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
        $this->_init(ComponentVariableModel::class, ComponentVariableResource::class);
    }
}
