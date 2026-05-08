<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for WhatsApp template categories.
 */
class ConfigTemplateCategory implements OptionSourceInterface
{
    /**
     * Return template category options.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'Utility', 'label' => __('Utility')],
            ['value' => 'Marketing', 'label' => __('Marketing')],
        ];
    }
}
