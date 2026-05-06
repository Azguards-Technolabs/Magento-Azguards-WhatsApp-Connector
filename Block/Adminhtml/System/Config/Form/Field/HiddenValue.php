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
        // getEscapedValue() already applies HTML escaping for attributes
        $value = (string)$element->getEscapedValue();

        if ($value === '') {
            $idMap = [
                'whatsapp_template_order_template_event_code'              => 'order_created',
                'whatsapp_template_order_invoice_template_event_code'      => 'order_invoice',
                'whatsapp_template_order_shipment_template_event_code'     => 'order_shipment',
                'whatsapp_template_order_cancellation_template_event_code' => 'order_cancellation',
                'whatsapp_template_order_credit_memo_template_event_code'  => 'order_credit_memo',
            ];

            $elementId = $element->getId();
            if (isset($idMap[$elementId])) {
                $value = $this->escapeHtmlAttr($idMap[$elementId]);
            }
        }

        return sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s"/>',
            $element->getHtmlId(),
            $element->getName(),
            $value
        );
    }
}
