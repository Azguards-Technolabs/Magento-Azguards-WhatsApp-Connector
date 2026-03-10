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
class OrderCreditMemo extends Field
{
    public const XML_PATH_ORDER_CREDIT_MEMO = "whatsApp_conector/order_credit_memo/order_credit_memo_variable";
    /**
     * Indices constructor.
     * @param IndexRepositoryInterface $indexService
     * @param Context $context
     */
    public $helper;

    public function __construct(
        Context $context,
        ApiHelper $helper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->setTemplate('Azguards_WhatsAppConnect::config/form/field/orderCreditMemo.phtml');
    }

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

            // Credit Memo Attributes
            'creditmemo_id' => __('Credit Memo ID'),
            'increment_id' => __('Credit Memo Number'),
            'created_at' => __('Credit Memo Created At'),
            'updated_at' => __('Credit Memo Updated At'),
            'grand_total' => __('Credit Memo Total'),
            'subtotal' => __('Credit Memo Subtotal'),
            'tax_amount' => __('Credit Memo Tax Amount'),
            'shipping_amount' => __('Credit Memo Shipping Amount'),
            'state' => __('Credit Memo Status'),
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);

        return $this->_toHtml();
    }

    /**
     * Available indexes
     *
     * @return IndexInterface[]
     */
   public function getOptionData($option)
    {
        if(!empty($option)) {
            return $option;
        }
        // Fetch the stored configuration data
        $userRegistrationData = $this->helper->getConfigValue(self::XML_PATH_ORDER_CREDIT_MEMO);
       $decodedData = (!empty($userRegistrationData) && is_string($userRegistrationData)) ? json_decode($userRegistrationData, true) : [];
       foreach ($decodedData as &$index) {
            foreach (['title', 'order', 'limit', 'type', 'identifier'] as $key) {
                if (isset($index[$key]) && is_string($index[$key])) {
                    $index[$key] = str_replace('"', '', $index[$key]); // Remove double quotes
                }
            }
        }
        if(empty($decodedData)) {
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
            return 'groups[order_credit_memo][fields][order_credit_memo_variable][value][' . $index['identifier'] . ']';
        }
        return $element->getName() . '[' . $index['identifier'] . ']';
        // return $this->getElement()->getName() . '[' . $index['identifier'] . ']';
    }

    /**
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
