<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

/**
 * Preview block for Order Credit Memo WhatsApp template.
 */
class PreviewCreditMemo extends Preview
{
    /**
     * Get initial configuration for the builder.
     *
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getCreditMemoTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * Get configuration group name.
     *
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_credit_memo_template';
    }

    /**
     * Get event code.
     *
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_credit_memo';
    }

    /**
     * Return event-specific variables.
     *
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
                        [
                            'label' => __('Refund ID'),
                            'badge' => 'increment_id',
                            'value' => '{{var creditmemo.increment_id}}'
                        ],
                        [
                            'label' => __('Refund Amount'),
                            'badge' => 'grand_total',
                            'value' => '{{var creditmemo.grand_total}}'
                        ],
                        [
                            'label' => __('Refund Date'),
                            'badge' => 'created_at',
                            'value' => '{{var creditmemo.created_at}}'
                        ],
                        [
                            'label' => __('Adjustment Refund'),
                            'badge' => 'adjustment_positive',
                            'value' => '{{var creditmemo.adjustment_positive}}'
                        ],
                        [
                            'label' => __('Adjustment Fee'),
                            'badge' => 'adjustment_negative',
                            'value' => '{{var creditmemo.adjustment_negative}}'
                        ],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
