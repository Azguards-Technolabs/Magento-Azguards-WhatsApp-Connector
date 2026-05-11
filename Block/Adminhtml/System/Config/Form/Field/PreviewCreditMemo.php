<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

class PreviewCreditMemo extends Preview
{

    /**
     * Get initial  config

     * @return array
     */
    public function getInitialConfig(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $config = $this->templateConfig->getCreditMemoTemplateConfig($storeId ?: null);

        return $this->enrichConfigWithMetaStatus($config);
    }

    /**
     * Get group name

     * @return string
     */
    protected function getGroupName(): string
    {
        return 'order_credit_memo_template';
    }

    /**
     * Get event code

     * @return string
     */
    protected function getEventCode(): string
    {
        return 'order_credit_memo';
    }

    /**
     * Get variable groups

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
