<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewCancellation extends Preview
{
    /**
     * @param Context $context
     * @param VariableResolver $variableResolver
     * @param WhatsAppTemplateConfig $templateConfig
     * @param Json $json
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        VariableResolver $variableResolver,
        WhatsAppTemplateConfig $templateConfig,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $variableResolver, $templateConfig, $json, $data);
    }

    /**
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->templateConfig->getCancellationTemplateConfig($storeId ?: null);
    }

    /**
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_cancellation_template';
    }

    /**
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_cancellation';
    }

    /**
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
                        ['label' => __('Cancel Reason'), 'badge' => 'cancel_reason', 'value' => '{{var order.cancel_reason}}'],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
