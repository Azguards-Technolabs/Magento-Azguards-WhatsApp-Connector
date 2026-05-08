<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

/**
 * Preview block for Order Invoice WhatsApp template.
 */
class PreviewInvoice extends Preview
{
    /**
     * Get initial configuration for the builder.
     *
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getInvoiceTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * Get configuration group name.
     *
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_invoice_template';
    }

    /**
     * Get event code.
     *
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_invoice';
    }

    /**
     * Return event-specific variables.
     *
     * @return array
     */
    public function getVariableGroups(): array
    {
        $groups = parent::getVariableGroups();

        $groups['invoice'] = [
            'label' => __('Invoice'),
            'subgroups' => [
                [
                    'label' => __('Invoice Details'),
                    'variables' => [
                        [
                            'label' => __('Invoice Number'),
                            'badge' => 'increment_id',
                            'value' => '{{var invoice.increment_id}}'
                        ],
                        ['label' => __('Invoice State'), 'badge' => 'state', 'value' => '{{var invoice.state}}'],
                        [
                            'label' => __('Total Amount'),
                            'badge' => 'grand_total',
                            'value' => '{{var invoice.grand_total}}'
                        ],
                        [
                            'label' => __('Invoice Date'),
                            'badge' => 'created_at',
                            'value' => '{{var invoice.created_at}}'
                        ],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
