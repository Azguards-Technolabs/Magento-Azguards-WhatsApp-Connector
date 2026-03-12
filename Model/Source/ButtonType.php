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
            ['value' => 'quick_reply', 'label' => __('Quick Reply (Button)')],
            ['value' => 'url', 'label' => __('Website Link (URL)')],
            ['value' => 'phone', 'label' => __('Call Phone (Number)')]
        ];
    }
}
