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
            ['value' => 'TEXT', 'label' => __('Standard (Text Only)')],
            ['value' => 'IMAGE', 'label' => __('Image Template (Media)')],
            ['value' => 'VIDEO', 'label' => __('Video Template')],
            ['value' => 'DOCUMENT', 'label' => __('Document Template')],
            ['value' => 'CAROUSEL', 'label' => __('Carousel Template')],
            ['value' => 'OTP', 'label' => __('OTP Template')],
            ['value' => 'LTO', 'label' => __('Limited Time Offer (LTO)')],
            ['value' => 'CATALOG', 'label' => __('Catalog Template')],
            ['value' => 'COUPON_CODE', 'label' => __('Coupon Code Template')],
            ['value' => 'SPM', 'label' => __('SPM Template')],
            ['value' => 'MPM', 'label' => __('MPM Template')],
            ['value' => 'LOCATION', 'label' => __('Location Template')]
        ];
    }
}
