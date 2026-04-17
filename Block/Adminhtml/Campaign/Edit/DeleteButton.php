<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Campaign\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getButtonData()
    {
        $id = $this->context->getRequest()->getParam('id');
        if (!$id) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to do this?') . '\', \'' . $this->getDeleteUrl() . '\')',
            'sort_order' => 20,
        ];
    }

    private function getDeleteUrl(): string
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['id' => $this->context->getRequest()->getParam('id')]);
    }
}
