<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Template
 */
class Template extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('whatsapp_templates', 'id');
    }
}
