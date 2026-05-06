<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewCreditMemo extends Preview
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
        return $this->templateConfig->getCreditMemoTemplateConfig($storeId ?: null);
    }

    /**
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_credit_memo_template';
    }

    /**
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_credit_memo';
    }

    /**
     * @return array
     */
    public function getVariableGroups(): array
    {
        $groups = parent::getVariableGroups();

        $groups['creditmemo'] = [
            'label' => __('Credit Memo'),
            'subgroups' => [
                [
                    'label' => __('Refund Details'),
                    'variables' => [
                        ['label' => __('Refund ID'), 'badge' => 'increment_id', 'value' => '{{var creditmemo.increment_id}}'],
                        ['label' => __('Refund Amount'), 'badge' => 'grand_total', 'value' => '{{var creditmemo.grand_total}}'],
                        ['label' => __('Refund Date'), 'badge' => 'created_at', 'value' => '{{var creditmemo.created_at}}'],
                        ['label' => __('Adjustment Refund'), 'badge' => 'adjustment_positive', 'value' => '{{var creditmemo.adjustment_positive}}'],
                        ['label' => __('Adjustment Fee'), 'badge' => 'adjustment_negative', 'value' => '{{var creditmemo.adjustment_negative}}'],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
