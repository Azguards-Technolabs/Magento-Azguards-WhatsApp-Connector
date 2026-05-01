<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Template\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton implements ButtonProviderInterface
{
    /**
     * Return save-and-continue button configuration.
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'whatsapp_template_form.whatsapp_template_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    ['back' => 'continue']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'sort_order' => 80,
        ];
    }
}
