<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;

class OrderCancel implements ObserverInterface
{
    public const XML_PATH_SEARCHABLE_DROPDOWN_ORDER_CANCEL =
    "whatsApp_conector/order_cancellation/searchable_dropdown_order_cancellation";
    public const XML_PATH_ORDER_CANCEL_VERIBLE =
    "whatsApp_conector/order_cancellation/order_cancellation_variable";
    public const XML_PATH_ENABLE_MODULES = "whatsApp_conector/general/enable";

     /**
      * @var ApiHelper
      */
    protected $apiHelper;
     /**
      * @var Logger
      */
    protected $logger;

    /**
     * OrderCancel construct
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
     * Execute function
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $orderCancelTempaletId = $this->apiHelper->getConfigValue(
                self::XML_PATH_SEARCHABLE_DROPDOWN_ORDER_CANCEL
            );
            $tempaletVerible = $this->apiHelper->getConfigValue(
                self::XML_PATH_ORDER_CANCEL_VERIBLE
            );
            $enable = $this->apiHelper->getConfigValue(self::XML_PATH_ENABLE_MODULES);
            if ($orderCancelTempaletId && $enable) {
                $tempaletVeribleData = json_decode($tempaletVerible, true);
                $tempaletVeribleDetails = [];
                foreach ($tempaletVeribleData as $value) {
                    $key = $value["order"];
                    $property = $value['limit'];
                    $methodName = 'get' . str_replace('_', '', ucwords($property, '_'));
                    if (method_exists($order, $methodName)) {
                        $tempaletVeribleDetails[$key] = $order->$methodName();
                    } else {
                        $tempaletVeribleDetails[$key] = $order->getData($property);
                    }
                }
                $userDetail = $this->apiHelper->getUserDetailData($order);
                $this->apiHelper->sendMessage(
                    $orderCancelTempaletId,
                    $tempaletVeribleDetails,
                    'OrderCancel',
                    $userDetail
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in OrderCancel Observer: " . $e->getMessage());
        }
    }
}
