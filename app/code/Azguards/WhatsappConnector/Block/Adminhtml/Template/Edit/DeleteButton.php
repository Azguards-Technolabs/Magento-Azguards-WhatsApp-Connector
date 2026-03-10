<?php
namespace Azguards\WhatsappConnector\Block\Adminhtml\Template\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class DeleteButton implements ButtonProviderInterface
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getButtonData()
    {
        $data = [];
        $templateId = $this->context->getRequest()->getParam('template_id');
        if ($templateId) {
            $data = [
                'label' => __('Delete Template'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''
                    . __('Are you sure you want to delete this template from Magento and WhatsApp ERP?')
                    . '\', \'' . $this->context->getUrlBuilder()->getUrl('*/*/delete', ['template_id' => $templateId]) . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
