<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultPage = $this->resultPageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Template'));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Template'));
        }

        return $resultPage;
    }
}
