<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Ui\Component\Listing\Column;

use Azguards\WhatsAppConnect\Model\Source\CampaignCustomerGroup;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CampaignCustomerGroups extends Column
{
    private CampaignCustomerGroup $customerGroupSource;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CampaignCustomerGroup $customerGroupSource,
        array $components = [],
        array $data = []
    ) {
        $this->customerGroupSource = $customerGroupSource;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $labelMap = [];
        foreach ($this->customerGroupSource->toOptionArray() as $option) {
            $labelMap[(string)$option['value']] = (string)$option['label'];
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $rawValue = $item['customer_group_ids'] ?? '[]';
            $groupIds = json_decode((string)$rawValue, true);
            if (!is_array($groupIds)) {
                $groupIds = array_filter(array_map('trim', explode(',', (string)$rawValue)));
            }

            $labels = [];
            foreach ($groupIds as $groupId) {
                $groupId = (string)$groupId;
                $labels[] = $labelMap[$groupId] ?? $groupId;
            }

            $item['customer_group_ids'] = implode(', ', array_filter($labels));
        }

        return $dataSource;
    }
}
