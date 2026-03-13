<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TemplateType implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'TEXT', 'label' => __('Text')],
            ['value' => 'IMAGE', 'label' => __('Image')],
            ['value' => 'CAROUSEL', 'label' => __('Carousel')],
        ];
    }
}
