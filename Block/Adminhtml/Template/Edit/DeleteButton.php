<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Template\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class DeleteButton implements ButtonProviderInterface
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getButtonData()
    {
        $data = [];
        $id = $this->context->getRequest()->getParam('id');
        if ($id) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to do this?') . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    private function getDeleteUrl()
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['id' => $this->context->getRequest()->getParam('id')]);
    }
}
