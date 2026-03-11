<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;

class AbandonedCart implements ObserverInterface
{
    
    public const XML_PATH_SEARCHABLE_DROPDOWN_ABANDON_CART =
    "whatsApp_conector/abandon_cart/searchable_dropdown_abandon_cart";
    public const XML_PATH_ABANDON_CART_VERIABLE =
    "whatsApp_conector/abandon_cart/abandoned_cart_variable";

    /**
     * @var ApiHelper
     */
    protected $apiHelper;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * AbandonedCart constructor
     *
     * @param ApiHelper $apiHelper
     * @param Logger $logger
     */
    public function __construct(
        ApiHelper $apiHelper,
        Logger $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $abandonCartTempaletId = $this->apiHelper->getConfigValue(
                self::XML_PATH_SEARCHABLE_DROPDOWN_ABANDON_CART
            );
            $abandonCartTempaletVerible = $this->apiHelper->getConfigValue(
                self::XML_PATH_ABANDON_CART_VERIABLE
            );
            // $customerData = $this->apiHelper->getCustomerDetails($order, $abandonCartTempaletId);
            // $customerId = $this->apiHelper->getContactId();
            // $response = $this->apiHelper->sendMessage(
            // $abandonCartTempaletId,
            // $abandonCartTempaletVerible,
            // 'AbandonedCart'
        // );
        } catch (\Exception $e) {
            $this->logger->error("Error in AbandonedCart Observer: " . $e->getMessage());
        }
    }
}
