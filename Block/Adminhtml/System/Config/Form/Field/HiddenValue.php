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
                'whatsApp_conector_user_registration_template_event_code'  => 'customer_registration',
                'whatsApp_conector_order_template_event_code'              => 'order_created',
                'whatsApp_conector_order_invoice_template_event_code'      => 'order_invoice',
                'whatsApp_conector_order_shipment_template_event_code'     => 'order_shipment',
                'whatsApp_conector_order_cancellation_template_event_code' => 'order_cancellation',
                'whatsApp_conector_order_credit_memo_template_event_code'  => 'order_credit_memo',
                'whatsApp_conector_abandoned_cart_template_event_code'     => 'abandon_cart',
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
