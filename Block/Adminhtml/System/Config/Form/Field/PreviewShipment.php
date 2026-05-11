<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewShipment extends Preview
{

    /**
     * GetInitialConfig
     *
     * @return array
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getShipmentTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * GetGroupName
     *
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_shipment_template';
    }

    /**
     * GetEventCode
     *
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_shipment';
    }

    /**
     * GetVariableGroups
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
