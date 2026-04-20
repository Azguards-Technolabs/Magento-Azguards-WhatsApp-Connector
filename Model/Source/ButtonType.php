<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ButtonType implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('None')],
            ['value' => 'QUICK_REPLY', 'label' => __('Quick Reply')],
            ['value' => 'URL', 'label' => __('URL (Website Link)')],
            ['value' => 'PHONE_NUMBER', 'label' => __('Phone Number (Call Button)')],
            ['value' => 'COPY_CODE', 'label' => __('Copy Coupon Code (COPY_CODE)')]
        ];
    }
}
