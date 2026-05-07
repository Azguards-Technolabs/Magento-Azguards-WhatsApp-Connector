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
class UserRegistration extends Field
{
    public const XML_PATH_USER_REGISTRATION = "whatsApp_conector/user_registration/index";
    /**
     * @var ApiHelper
     */
    public $helper;

    /**
     * UserRegistration construct
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
            'Azguards_WhatsAppConnect::config/form/field/userRegistration.phtml'
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
            'firstname' => __('First Name'),
            'lastname' => __('Last Name'),
            'email' => __('Email'),
            'dob' => __('Date of Birth'),
            'gender' => __('Gender'),
            'created_at' => __('Created At'),
            'phone_number' => __('Phone Number'),
            'group_id' => __('Group ID'),
            'billing_address' => __('Billing Address'),
            'shipping_address' => __('Shipping Address')
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
    * @param [type] $option
    * @return void
    */
    public function getOptionData($option)
    {
        
        if (!empty($option)) {
            return $option;
        }
        // Fetch the stored configuration data
        $userRegistrationData = $this->helper->getConfigValue(self::XML_PATH_USER_REGISTRATION);
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
            return 'groups[user_registration][fields][index][value][' .
            $index['identifier'] . ']';
        }
        return $element->getName() . '[' . $index['identifier'] . ']';
        //return $this->getElement()->getName() . '[' . $index['identifier'] . ']';
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
