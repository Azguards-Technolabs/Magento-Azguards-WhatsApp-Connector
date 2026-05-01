<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CampaignTargetOptions implements OptionSourceInterface
{
    /**
     * Return campaign target mode options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'groups', 'label' => __('Customer Groups')],
            ['value' => 'contacts', 'label' => __('Specific Contacts')]
        ];
    }
}
