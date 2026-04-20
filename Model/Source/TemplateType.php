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
            ['value' => 'COUPON_CODE', 'label' => __('Coupon Code')],
            ['value' => 'MEDIA', 'label' => __('Media (Image, Video, Document)')],
            ['value' => 'CAROUSEL', 'label' => __('Carousel')],
        ];
    }
}
