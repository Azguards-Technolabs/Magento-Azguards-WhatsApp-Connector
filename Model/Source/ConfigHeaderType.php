<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConfigHeaderType implements OptionSourceInterface
{
    /**
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'none', 'label' => __('No Header')],
            ['value' => 'text', 'label' => __('Text')],
            ['value' => 'image', 'label' => __('Image')],
        ];
    }
}
