<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Plugin;

class UiComponentFactoryPlugin
{
    /**
     * Magento UI component definition does not include a native "datetime" form element type.
     * Some customer EAV attributes can still end up using frontend_input = "datetime", which
     * causes UiComponentFactory to receive $componentType = "datetime" and crash with null
     * component data. Remap to supported "date" type to avoid admin customer form fatals.
     *
     * @param \Magento\Framework\View\Element\UiComponentFactory $subject
     * @param string $componentName
     * @param string|null $componentType
     * @param array $arguments
     * @return array
     */
    public function beforeCreate(
        \Magento\Framework\View\Element\UiComponentFactory $subject,
        $componentName,
        $componentType = null,
        array $arguments = []
    ): array {
        if ($componentType === 'datetime') {
            // Keep it generic: any datetime element should at least not crash the UI component tree.
            $componentType = 'date';
        }

        return [$componentName, $componentType, $arguments];
    }
}
