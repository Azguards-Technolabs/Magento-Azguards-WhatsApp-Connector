<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Azguards\WhatsAppConnect\Model\Campaign;

class CampaignActions extends Column
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare campaign action links for each grid row.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $name = $this->getData('name');
            $status = (string)($item['status'] ?? '');
            $isEditable = (!$this->statusMatches($status, Campaign::STATUS_COMPLETED) &&
                           !$this->statusMatches($status, Campaign::STATUS_PROCESSING) &&
                           (int)($item['sent_count'] ?? 0) === 0);

            if ($isEditable) {
                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'whatsappconnect/campaign/edit',
                        ['id' => $item['entity_id']]
                    ),
                    'label' => __('Edit'),
                ];
            }

            if ($this->statusMatches($status, Campaign::STATUS_PROCESSING)
                || $this->statusMatches($status, Campaign::STATUS_PENDING)
                || $this->statusMatches($status, Campaign::STATUS_SCHEDULED)
                || $this->statusMatches($status, Campaign::STATUS_RESCHEDULED)) {
                $item[$name]['pause'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'whatsappconnect/campaign/status',
                        ['id' => $item['entity_id'], 'action' => 'pause']
                    ),
                    'label' => __('Pause'),
                ];
            }

            if ($this->statusMatches($status, Campaign::STATUS_PAUSED)) {
                $item[$name]['resume'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'whatsappconnect/campaign/status',
                        ['id' => $item['entity_id'], 'action' => 'resume']
                    ),
                    'label' => __('Resume'),
                ];
            }

            if (isset($item['failed_count']) && (int)$item['failed_count'] > 0) {
                $item[$name]['retry'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'whatsappconnect/campaign/retry',
                        ['id' => $item['entity_id']]
                    ),
                    'label' => __('Retry Failed'),
                    'confirm' => [
                        'title' => __('Retry %1', $item['campaign_name'] ?? ''),
                        'message' => __(
                            'Are you sure you want to retry failed messages for campaign %1?',
                            $item['campaign_name'] ?? ''
                        ),
                    ],
                ];
            }

            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl('whatsappconnect/campaign/delete', ['id' => $item['entity_id']]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete %1', $item['campaign_name'] ?? ''),
                    'message' => __('Are you sure you want to delete campaign %1?', $item['campaign_name'] ?? ''),
                ],
            ];
        }

        return $dataSource;
    }

    /**
     * Compare campaign statuses case-insensitively.
     *
     * @param string $status
     * @param string $expected
     * @return bool
     */
    private function statusMatches(string $status, string $expected): bool
    {
        return strtoupper($status) === strtoupper($expected);
    }
}
