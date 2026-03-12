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
            ['value' => 'QUICK_REPLY', 'label' => __('Quick Reply (Button)')],
            ['value' => 'URL', 'label' => __('Website Link (URL)')],
            ['value' => 'PHONE_NUMBER', 'label' => __('Call Phone (Number)')],
            ['value' => 'COPY_CODE', 'label' => __('Copy Code')],
            ['value' => 'OTP', 'label' => __('OTP Button')]
        ];
    }
}
