<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Azguards\WhatsAppConnect\Logger\Logger;

class CreateOrderAfter implements ObserverInterface
{
    public const XML_PATH_SEARCHABLE_DROPDOWN_ORDER_CREATE =
    "whatsApp_conector/order_creation/searchable_dropdown_order_create";
    public const XML_PATH_ORDER_CREATE_VERIABLE =
    "whatsApp_conector/order_creation/order_create_variable";

    public const XML_PATH_SEARCHABLE_DROPDOWN_PRODUCT_NOTIFICATION =
    "whatsApp_conector/out_of_stock_product_notification/searchable_dropdown_product_notification";
    public const XML_PATH_PRODUCT_NOTIFICATION_VERIABLE =
    "whatsApp_conector/out_of_stock_product_notification/product_notification_variable";
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
     * @var StockRegistry
     */
    protected $stockRegistry;

    /**
     * CreateOrderAfter construct
     *
     * @param ApiHelper $apiHelper
     * @param StockRegistryInterface $stockRegistry
     * @param Logger $logger
     */
    public function __construct(
        ApiHelper $apiHelper,
        StockRegistryInterface $stockRegistry,
        Logger $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $orderCreateTempaletId = $this->apiHelper->getConfigValue(
                self::XML_PATH_SEARCHABLE_DROPDOWN_ORDER_CREATE
            );
            $orderCreateTempaletVerible = $this->apiHelper->getConfigValue(
                self::XML_PATH_ORDER_CREATE_VERIABLE
            );
            $enable = $this->apiHelper->getConfigValue(self::XML_PATH_ENABLE_MODULES);
            if ($orderCreateTempaletId && $enable) {
                $tempaletVeribleData = json_decode($orderCreateTempaletVerible, true);
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
                $response = $this->apiHelper->sendMessage(
                    $orderCreateTempaletId,
                    $tempaletVeribleDetails,
                    'orderCreate',
                    $userDetail
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in CreateOrderAfter Observer: " . $e->getMessage());
        }
    }

    /**
     * Send Out Of Stock Notification
     *
     * @param [type] $order
     * @return void
     */
    public function sendOutOfStockNotification($order)
    {
        try {
            foreach ($order->getAllItems() as $item) {
                $productId = $item->getProductId();
                $qtyOrdered = $item->getQtyOrdered();

                // Get stock item
                $stockItem = $this->stockRegistry->getStockItemBySku($item->getSku());

                if ($stockItem) {
                    $currentQty = $stockItem->getQty();
                    $newQty = max(0, $currentQty - $qtyOrdered); // Prevent negative stock

                    $orderCreateTempaletId = $this->apiHelper->getConfigValue(
                        self::XML_PATH_SEARCHABLE_DROPDOWN_PRODUCT_NOTIFICATION
                    );
                    $orderCreateTempaletVerible = $this->apiHelper->getConfigValue(
                        self::XML_PATH_PRODUCT_NOTIFICATION_VERIABLE
                    );
                    $tempaletVeribleData = json_decode($orderCreateTempaletVerible, true);
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
                    
                    if ($newQty <= 0) {
                        $userDetail = $this->apiHelper->getUserDetailData($order);
                        $response = $this->apiHelper->sendMessage(
                            $orderCreateTempaletId,
                            $tempaletVeribleDetails,
                            'sendOutOfStockNotification',
                            $userDetail
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in NotifyOutOfStock observer: " . $e->getMessage());
        }
    }
}
