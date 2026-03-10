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
class ProductNotification extends Field
{
    public const XML_PATH_OUT_OF_STOCK_PRODUCT_NOTIFICATION =
    "whatsApp_conector/out_of_stock_product_notification/product_notification_variable";
    /**
     * @var ApiHelper
     */
    public $helper;

    /**
     * ProductNotification construct
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
            'Azguards_WhatsAppConnect::config/form/field/productNotification.phtml'
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
             'notify_product_id' => __('Product ID'),
            'notify_product_name' => __('Product Name'),
            'notify_customer_email' => __('Customer Email'),
            'notify_requested_at' => __('Requested At'),
            'notify_status' => __('Notification Status'),
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
        $userRegistrationData = $this->helper->getConfigValue(self::XML_PATH_OUT_OF_STOCK_PRODUCT_NOTIFICATION);
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
            return 'groups[out_of_stock_product_notification][fields][product_notification_variable][value][' .
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
