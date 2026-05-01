<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class HeaderFormat implements OptionSourceInterface
{
    /**
     * Return supported header format options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'TEXT', 'label' => __('Text')],
            ['value' => 'IMAGE', 'label' => __('Image')],
            ['value' => 'VIDEO', 'label' => __('Video')],
            ['value' => 'DOCUMENT', 'label' => __('Document')]
        ];
    }
}
