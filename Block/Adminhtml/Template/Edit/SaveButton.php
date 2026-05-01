<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Template\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveButton implements ButtonProviderInterface
{
    /**
     * Return save button configuration.
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save Template'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
