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
            ['value' => 'QUICK_REPLY', 'label' => __('Add Quick Reply Button')],
            ['value' => 'URL', 'label' => __('Add URL Button')],
            ['value' => 'PHONE_NUMBER', 'label' => __('Add Phone Number Button')]
        ];
    }
}
