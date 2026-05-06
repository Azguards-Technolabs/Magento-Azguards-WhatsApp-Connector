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
     * @param Context $context
     * @param VariableResolver $variableResolver
     * @param WhatsAppTemplateConfig $templateConfig
     * @param Json $json
     * @param TemplateCollectionFactory $templateCollectionFactory
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        VariableResolver $variableResolver,
        WhatsAppTemplateConfig $templateConfig,
        Json $json,
        TemplateCollectionFactory $templateCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $variableResolver, $templateConfig, $json, $templateCollectionFactory, $data);
    }

    /**
     * @return array<string, string>
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getCancellationTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
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
