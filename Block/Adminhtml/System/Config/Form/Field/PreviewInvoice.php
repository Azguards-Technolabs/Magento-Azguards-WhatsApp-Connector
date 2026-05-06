<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewInvoice extends Preview
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
        $config = $this->templateConfig->getInvoiceTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_invoice_template';
    }

    /**
     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_invoice';
    }

    /**
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
                        ['label' => __('Invoice Number'), 'badge' => 'increment_id', 'value' => '{{var invoice.increment_id}}'],
                        ['label' => __('Invoice State'), 'badge' => 'state', 'value' => '{{var invoice.state}}'],
                        ['label' => __('Total Amount'), 'badge' => 'grand_total', 'value' => '{{var invoice.grand_total}}'],
                        ['label' => __('Invoice Date'), 'badge' => 'created_at', 'value' => '{{var invoice.created_at}}'],
                    ]
                ]
            ]
        ];

        return $groups;
    }
}
