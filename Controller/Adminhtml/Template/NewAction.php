<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;

class NewAction extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    public function execute()
    {
        $this->_forward('edit');
    }
}
