<?php
namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableRowsBuilder;

class OrderCancellation extends Action
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

    private TemplateVariableRowsBuilder $variableRowsBuilder;

    /**
     * OrderCancellation constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     * @param TemplateVariableRowsBuilder $variableRowsBuilder
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        TemplateVariableRowsBuilder $variableRowsBuilder
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
        $this->variableRowsBuilder = $variableRowsBuilder;
    }

    /**
     * Execute controller to render Cancellation template variables HTML block
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $templateId = $this->getRequest()->getParam('template_id');
        $fieldId = $this->getRequest()->getParam('field_id');
        $requesrUrl = $this->getRequest()->getParam('requesrUrl');

        $templateVerible = $this->variableRowsBuilder->buildByExternalTemplateId((string)$templateId);

        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock(
            \Azguards\WhatsAppConnect\Block\Adminhtml\Config\Form\Field\OrderCancellation::class
        )->setTemplate(
            'Azguards_WhatsAppConnect::config/form/field/orderCancellation.phtml'
        )->setData('template_id', $templateId)->setData('field_id', $fieldId)->setData('options', $templateVerible);

        $response = [
                'data' => $block->toHtml(),
                'id' => $fieldId
            ];
        return $resultJson->setData($response);
    }
}
