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
class OrderCancellation extends Field
{
    public const XML_PATH_ORDER_CANCELLATION =
    "whatsApp_conector/order_cancellation/order_cancellation_variable";
   /**
    * @var ApiHelper
    */
    public $helper;

    /**
     * Order Cancellation
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
            'Azguards_WhatsAppConnect::config/form/field/orderCancellation.phtml'
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
            // Order Attributes
            'entity_id' => __('Order ID'),
            'increment_id' => __('Order Number'),
            'status' => __('Order Status'),
            'customer_email' => __('Customer Email'),
            'customer_firstname' => __('Customer First Name'),
            'customer_lastname' => __('Customer Last Name'),
            'grand_total' => __('Grand Total'),
            'subtotal' => __('Subtotal'),
            'shipping_amount' => __('Shipping Amount'),
            'payment_method' => __('Payment Method'),
            'shipping_method' => __('Shipping Method'),
            'created_at' => __('Order Date'),
            'updated_at' => __('Last Updated'),
            'billing_address' => __('Billing Address'),
            'shipping_address' => __('Shipping Address'),

            // Canceled Order Attributes
            'canceled_at' => __('Canceled At'),
            'canceled_reason' => __('Cancellation Reason'),
            'canceled_by' => __('Canceled By'),
            'refund_amount' => __('Refund Amount'),
            'refund_status' => __('Refund Status'),
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
        $userRegistrationData = $this->helper->getConfigValue(self::XML_PATH_ORDER_CANCELLATION);
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
            return 'groups[order_cancellation][fields][order_cancellation_variable][value][' .
            $index['identifier'] . ']';
        }
        return $element->getName() . '[' . $index['identifier'] . ']';
        // return $this->getElement()->getName() . '[' . $index['identifier'] . ']';
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
