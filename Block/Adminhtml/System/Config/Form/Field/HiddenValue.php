<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class HiddenValue extends Field
{
    /**
     * Suppress the standard config row and render only the hidden input.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        return $this->_getElementHtml($element);
    }

    /**
     * Render a hidden config value while preserving form submission.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $value = (string)$element->getEscapedValue();
        if ($value === '' && $element->getId() === 'whatsapp_template_order_template_event_code') {
            $value = 'order_created';
        }

        return sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s"/>',
            $element->getHtmlId(),
            $element->getName(),
            $this->escapeHtmlAttr($value)
        );
    }
}
