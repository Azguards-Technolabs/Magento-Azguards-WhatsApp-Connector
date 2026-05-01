<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Azguards\WhatsAppConnect\Model\Campaign;
use Magento\Framework\Data\OptionSourceInterface;

class CampaignStatus implements OptionSourceInterface
{
    /**
     * Return available campaign status options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'SCHEDULED', 'label' => __('Scheduled')],
            ['value' => Campaign::STATUS_SCHEDULED, 'label' => __('Scheduled')],
            ['value' => 'RESCHEDULED', 'label' => __('Rescheduled')],
            ['value' => Campaign::STATUS_RESCHEDULED, 'label' => __('Rescheduled')],
            ['value' => 'PAUSED', 'label' => __('Paused')],
            ['value' => Campaign::STATUS_PAUSED, 'label' => __('Paused')],
            ['value' => 'PENDING', 'label' => __('Pending')],
            ['value' => Campaign::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => Campaign::STATUS_PROCESSING, 'label' => __('Processing')],
            ['value' => 'COMPLETED', 'label' => __('Completed')],
            ['value' => Campaign::STATUS_COMPLETED, 'label' => __('Completed')],
            ['value' => 'FAILED', 'label' => __('Failed')],
            ['value' => Campaign::STATUS_FAILED, 'label' => __('Failed')],
        ];
    }
}
