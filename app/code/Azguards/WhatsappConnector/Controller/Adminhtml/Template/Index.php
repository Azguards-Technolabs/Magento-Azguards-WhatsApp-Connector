<?php
namespace Azguards\WhatsappConnector\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsappConnector::templates';

    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Azguards_WhatsappConnector::templates');
        $resultPage->getConfig()->getTitle()->prepend(__('WhatsApp Templates'));

        return $resultPage;
    }
}
