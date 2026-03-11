<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TemplateCategory implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'Marketing', 'label' => __('Marketing')],
            ['value' => 'Utility', 'label' => __('Utility')],
            ['value' => 'Authentication', 'label' => __('Authentication')]
        ];
    }
}
