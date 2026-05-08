<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

/**
 * Preview block for Order Shipment WhatsApp template.
 */
class PreviewShipment extends Preview
{
    /**
     * Get initial configuration for the builder.
     *
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getShipmentTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * Get configuration group name.
     *
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_shipment_template';
    }

    /**
     * Get event code.
     *
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_shipment';
    }

    /**
     * Return event-specific variables.
     *
     * @return array
     */
    public function getVariableGroups(): array
    {
        $groups = parent::getVariableGroups();

        $groups['shipment'] = [
            'label' => __('Shipment'),
            'subgroups' => [
                [
                    'label' => __('Shipment Details'),
                    'variables' => [
                        [
                            'label' => __('Tracking Number'),
                            'badge' => 'tracking_number',
                            'value' => '{{var shipment.tracking_number}}'
                        ],
                        [
                            'label' => __('Carrier Name'),
                            'badge' => 'carrier_name',
                            'value' => '{{var shipment.carrier_name}}'
                        ],
                        [
                            'label' => __('Shipment ID'),
                            'badge' => 'increment_id',
                            'value' => '{{var shipment.increment_id}}'
                        ],
                        [
                            'label' => __('Shipment Date'),
                            'badge' => 'created_at',
                            'value' => '{{var shipment.created_at}}'
                        ],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
