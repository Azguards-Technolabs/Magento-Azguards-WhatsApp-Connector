<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Azguards\WhatsAppConnect\Block\Adminhtml\Config\Form\Field\AbandonCart as AbandonCartConfigBlock;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableRowsBuilder;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class Abandoncart extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::config';

    /**
     * @var JsonFactory
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var RawFactory
     */
    protected RawFactory $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected LayoutFactory $layoutFactory;

    /**
     * @var TemplateVariableRowsBuilder
     */
    private TemplateVariableRowsBuilder $variableRowsBuilder;

    /**
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
     * Return rendered variable mapping HTML for the abandon cart template field.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $templateId = (string)$this->getRequest()->getParam('template_id');
        $fieldId = (string)$this->getRequest()->getParam('field_id');

        $templateVariables = $this->variableRowsBuilder->buildByExternalTemplateId($templateId);

        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock(AbandonCartConfigBlock::class)
            ->setTemplate('Azguards_WhatsAppConnect::config/form/field/abandonCart.phtml')
            ->setData('template_id', $templateId)
            ->setData('field_id', $fieldId)
            ->setData('options', $templateVariables);

        return $resultJson->setData([
            'data' => $block->toHtml(),
            'id' => $fieldId,
        ]);
    }
}
