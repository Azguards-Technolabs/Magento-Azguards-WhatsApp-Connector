<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Field renderer for WhatsApp template variable selector.
 */
class VariableSelector extends Field
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('Azguards_WhatsAppConnect::system/config/field/variable-selector.phtml');
    }

    /**
     * Render the variable selector field.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->setData('element', $element);

        return $this->_toHtml();
    }

    /**
     * Get variable groups for the selector.
     *
     * @return array<string, array<int, string>>
     */
    public function getVariableGroups(): array
    {
        return [
            'Customer' => [
                'customer_firstname',
                'customer_lastname',
                'customer_email',
            ],
            'Order' => [
                'increment_id',
                'grand_total',
                'total_qty_ordered',
            ],
            'Address' => [
                'city',
                'country_id',
            ],
            'Items (Loop Supported)' => [
                'items.name',
                'items.qty',
                'items.price',
            ],
        ];
    }
}
