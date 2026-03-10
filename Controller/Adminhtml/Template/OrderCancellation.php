<?php
namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class OrderCancellation extends Action
{
    protected $resultJsonFactory;
    protected $resultRawFactory;
    protected $layoutFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        ApiHelper $apiHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
        $this->apiHelper = $apiHelper;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $templateId = $this->getRequest()->getParam('template_id');
        $fieldId = $this->getRequest()->getParam('field_id');
        $requesrUrl = $this->getRequest()->getParam('requesrUrl');

        $templateVerible = $this->apiHelper->getTemplateVariable($templateId);

        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock(\Azguards\WhatsAppConnect\Block\Adminhtml\Config\Form\Field\OrderCancellation::class)->setTemplate('Azguards_WhatsAppConnect::config/form/field/orderCancellation.phtml')->setData('template_id', $templateId)->setData('field_id', $fieldId)->setData('options', $templateVerible);
        

        $resultRaw = $this->resultRawFactory->create();
        $response = [
                'data' => $block->toHtml(),
                'id' => $fieldId
            ];
        return $resultJson->setData($response);
    }
}
