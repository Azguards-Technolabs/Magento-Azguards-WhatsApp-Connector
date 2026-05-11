<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewCancellation extends Preview
{
    /**
     * GetInitialConfig
     *
     * @return array
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getCancellationTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * GetGroupName
     *
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_cancellation_template';
    }

    /**
     * GetEventCode
     *
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_cancellation';
    }

    /**
     * GetVariableGroups
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
                        [
                            'label' => __('Order Status'),
                            'badge' => 'status',
                            'value' => '{{var order.status}}'
                        ],
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
