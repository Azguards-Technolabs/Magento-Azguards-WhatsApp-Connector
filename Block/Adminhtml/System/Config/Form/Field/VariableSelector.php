<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

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
     * Render element

     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->setData('element', $element);

        return $this->_toHtml();
    }

    /**
     * Get variable groups

     * @return array
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
