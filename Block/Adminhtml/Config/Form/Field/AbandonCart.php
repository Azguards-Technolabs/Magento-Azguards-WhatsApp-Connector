<?php

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Azguards\WhatsAppConnect\Helper\ApiHelper;

/**
 * @method AbstractElement getElement()
 * @method $this setElement(AbstractElement $element)
 */
class AbandonCart extends Field
{
    public const XML_PATH_ABANDON_CART =
    "whatsApp_conector/abandon_cart/abandoned_cart_variable";
    
    /**
     * @var ApiHelper
     */
    public $helper;

    /**
     * Abandon Cart
     *
     * @param Context $context
     * @param ApiHelper $helper
     */
    public function __construct(
        Context $context,
        ApiHelper $helper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate(
            'Azguards_WhatsAppConnect::config/form/field/indices.phtml'
        );
    }
    /**
     * Get Dropdown Options
     *
     * @return void
     */
    public function getDropdownOptions()
    {
        return [
             'cart_id' => __('Cart ID'),
            'cart_created_at' => __('Cart Created At'),
            'cart_updated_at' => __('Cart Updated At'),
            'cart_grand_total' => __('Cart Grand Total'),
            'cart_subtotal' => __('Cart Subtotal'),
            'cart_items_count' => __('Cart Items Count'),
            'cart_items_qty' => __('Cart Items Quantity'),
            'cart_coupon_code' => __('Cart Coupon Code'),
            'cart_customer_email' => __('Cart Customer Email'),
            'cart_customer_firstname' => __('Cart Customer First Name'),
            'cart_customer_lastname' => __('Cart Customer Last Name'),
            'cart_is_guest' => __('Is Guest Cart'),
            'cart_status' => __('Cart Status'),
        ];
    }
    /**
     * Render
     *
     * @param AbstractElement $element
     * @return void
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);

        return $this->_toHtml();
    }

    /**
     * Available indexes
     *
     * @param array|string|int $option
     * @return void
     */
    public function getOptionData($option)
    {
        if (!empty($option)) {
            return $option;
        }
        // Fetch the stored configuration data
        $userRegistrationData = $this->helper->getConfigValue(self::XML_PATH_ABANDON_CART);
        $decodedData = (!empty($userRegistrationData) && is_string($userRegistrationData)) ?
         json_decode($userRegistrationData, true) : [];
        foreach ($decodedData as &$index) {
            foreach (['title', 'order', 'limit', 'type', 'identifier'] as $key) {
                if (isset($index[$key]) && is_string($index[$key])) {
                    $index[$key] = str_replace('"', '', $index[$key]); // Remove double quotes
                }
            }
        }
        if (empty($decodedData)) {
            return [];
        }
        return $decodedData;
    }

    /**
     * Index name
     *
     * @param IndexInterface $index
     * @return string
     */
    public function getNamePrefix($index)
    {
        $element = $this->getElement();
        if (!$element) {
            return 'groups[abandon_cart][fields][abandoned_cart_variable][value][' .
            $index['identifier'] . ']';
        }
        return $element->getName() . '[' . $index['identifier'] . ']';
    }

    /**
     * Get Value
     *
     * @param IndexInterface $index
     * @param string $item
     * @return string
     */
    public function getValue($index, $item)
    {
        $identifier = $index->getIdentifier();
            $values = $this->getElement()->getData('value');
        if (isset($values[$identifier]) && isset($values[$identifier][$item])) {
            return $values[$identifier][$item];
        }

        return false;
    }
}
