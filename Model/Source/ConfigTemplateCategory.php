<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConfigTemplateCategory implements OptionSourceInterface
{
    /**
     * Options getter

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
