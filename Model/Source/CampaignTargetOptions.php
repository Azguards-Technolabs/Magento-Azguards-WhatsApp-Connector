<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CampaignTargetOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'groups', 'label' => __('Customer Groups')],
            ['value' => 'contacts', 'label' => __('Specific Contacts')]
        ];
    }
}
