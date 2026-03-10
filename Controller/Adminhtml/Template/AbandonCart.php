<?php
namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class AbandonCart extends Action
{
    /**
     * @var JsonFactory
     */
      protected $resultJsonFactory;

      /**
       * @var RawFactory
       */
      protected $resultRawFactory;
  
      /**
       * @var LayoutFactory
       */
      protected $layoutFactory;
    /**
     * @var ApiHelper
     */
      protected $apiHelper;

      /**
       * AbandonCart construct
       *
       * @param Context $context
       * @param JsonFactory $resultJsonFactory
       * @param RawFactory $resultRawFactory
       * @param LayoutFactory $layoutFactory
       * @param ApiHelper $apiHelper
       */
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

    /**
     * Execute controller to render AbandonCart template variables HTML block
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $templateId = $this->getRequest()->getParam('template_id');
        $fieldId = $this->getRequest()->getParam('field_id');
        $requesrUrl = $this->getRequest()->getParam('requesrUrl');

        $templateVerible = $this->apiHelper->getTemplateVariable($templateId);

        // Load the layout and create a block
        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock(
            \Azguards\WhatsAppConnect\Block\Adminhtml\Config\Form\Field\AbandonCart::class
        )->setTemplate(
            'Azguards_WhatsAppConnect::config/form/field/abandonCart.phtml'
        )->setData('template_id', $templateId)->setData('field_id', $fieldId)->setData('options', $templateVerible);
        
        $response = [
                'data' => $block->toHtml(),
                'id' => $fieldId
            ];
        return $resultJson->setData($response);
    }
}
