<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Azguards\WhatsAppConnect\Model\Campaign;
use Magento\Framework\Data\OptionSourceInterface;

class CampaignStatus implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Campaign::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => Campaign::STATUS_PROCESSING, 'label' => __('Processing')],
            ['value' => Campaign::STATUS_COMPLETED, 'label' => __('Completed')],
            ['value' => Campaign::STATUS_FAILED, 'label' => __('Failed')],
        ];
    }
}
