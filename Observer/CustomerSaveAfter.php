<?php
namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\Registry;
use Azguards\WhatsAppConnect\Logger\Logger;
use Magento\Store\Model\StoreManagerInterface;

class CustomerSaveAfter implements ObserverInterface
{
    public const XML_PATH_SEARCHABLE_DROPDOWN = "whatsApp_conector/user_registration/searchable_dropdown";
    public const XML_PATH_USER_REGISTRATION_VERIBLE = "whatsApp_conector/user_registration/index";
    public const XML_PATH_ENABLE_MODULES = "whatsApp_conector/general/enable";

    protected $apiHelper;
    protected $logger;
    protected $registry;

    public function __construct(
        ApiHelper $apiHelper,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            $userTempaletId = $this->apiHelper->getConfigValue(self::XML_PATH_SEARCHABLE_DROPDOWN);
            $userTempaletVerible = $this->apiHelper->getConfigValue(self::XML_PATH_USER_REGISTRATION_VERIBLE);
            $enable = $this->apiHelper->getConfigValue(self::XML_PATH_ENABLE_MODULES);
            if ($userTempaletId && $enable) {
                $tempaletVeribleData = json_decode($userTempaletVerible, true);
                $tempaletVeribleDetails = [];

                foreach ($tempaletVeribleData as $value) {
                    $key = $value["order"];
                    $property = $value['limit'];
                    $methodName = 'get' . str_replace('_', '', ucwords($property, '_'));
                    $tempaletVeribleDetails[$key] = $customer->$methodName();
                }

                $customerData = $this->apiHelper->getCustomerUserDetails($customer, $userTempaletId);
                $customerSave = $this->registry->registry('customer_save_event');
                $userDetail = $this->getCustomerDetailData($customer);
                if (!$customerSave) {
                    $response = $this->apiHelper->sendMessage($userTempaletId, $tempaletVeribleDetails, 'CustomerSaveAfter', $userDetail);
                    $this->registry->register('customer_save_event', '1');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in CustomerSaveAfter Observer: " . $e->getMessage());
        } 
    }

    public function getCustomerDetailData($customer)
    {
        return [
            'firstName'     => $customer->getFirstname(),
            'lastName'      => $customer->getLastname(),
            'countryCode'   => '91', // You can fetch this dynamically if stored somewhere
            'mobileNumber'  => $customer->getCustomAttribute('mobile_number') 
                                ? $customer->getCustomAttribute('mobile_number')->getValue()
                                : '',
            'imageURL'      => 'https://randomuser.me/api/portraits/men/45.jpg',
            'email'         => $customer->getEmail(),
            'businessName'  => $customer->getCustomAttribute('business_name') 
                                ? $customer->getCustomAttribute('business_name')->getValue()
                                : '',
            'website'       => $this->storeManager->getStore()->getBaseUrl()
        ];
    }
}
