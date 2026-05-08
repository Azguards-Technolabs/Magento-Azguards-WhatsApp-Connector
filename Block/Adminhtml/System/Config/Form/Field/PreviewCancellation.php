<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

/**
 * Preview block for Order Cancellation WhatsApp template.
 */
class PreviewCancellation extends Preview
{
    /**
     * Get initial configuration for the builder.
     *
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getCancellationTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * Get configuration group name.
     *
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_cancellation_template';
    }

    /**
     * Get event code.
     *
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_cancellation';
    }

    /**
     * Return event-specific variables.
     *
     * @return array
     */
    public function getVariableGroups(): array
    {
        $groups = parent::getVariableGroups();

        $groups['cancellation'] = [
            'label' => __('Cancellation'),
            'subgroups' => [
                [
                    'label' => __('Cancellation Details'),
                    'variables' => [
                        ['label' => __('Order Status'), 'badge' => 'status', 'value' => '{{var order.status}}'],
                        [
                            'label' => __('Cancel Reason'),
                            'badge' => 'cancel_reason',
                            'value' => '{{var order.cancel_reason}}'
                        ],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
