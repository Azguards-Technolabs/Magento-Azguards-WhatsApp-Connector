<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;

class OrderShipped implements ObserverInterface
{
    
    public const XML_PATH_SEARCHABLE_DROPDOWN_ORDER_SHIPMENT = "whatsApp_conector/order_shipment/searchable_dropdown_order_shipment";
    public const XML_PATH_ORDER_SHIPMENT_VERIABLE = "whatsApp_conector/order_shipment/order_shipment_variable";
    public const XML_PATH_ENABLE_MODULES = "whatsApp_conector/general/enable";

    protected $apiHelper;
    protected $logger;

    public function __construct(
        ApiHelper $apiHelper,
        Logger $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $shipment = $observer->getEvent()->getShipment();
            $order = $shipment->getOrder();
            $shipmentTempaletId = $this->apiHelper->getConfigValue(self::XML_PATH_SEARCHABLE_DROPDOWN_ORDER_SHIPMENT);
            $shipmentTempaletVerible = $this->apiHelper->getConfigValue(self::XML_PATH_ORDER_SHIPMENT_VERIABLE);
            $enable = $this->apiHelper->getConfigValue(self::XML_PATH_ENABLE_MODULES);
            if ($shipmentTempaletId && $enable) {
                $tempaletVeribleData = json_decode($shipmentTempaletVerible, true);
                $tempaletVeribleDetails = [];


                foreach ($tempaletVeribleData as $value) {
                    $key = $value["order"];
                    $property = $value['limit'];
                    $methodName = 'get' . str_replace('_', '', ucwords($property, '_'));
                    
                    if ($property === 'tracks[0].track_number') {
                        $tracks = $shipment->getAllTracks();
                        $tempaletVeribleDetails[$key] = !empty($tracks) ? $tracks[0]->getTrackNumber() : '';
                    } elseif ($property === 'tracks[0].carrier_code') {
                        $tracks = $shipment->getAllTracks();
                        $tempaletVeribleDetails[$key] = !empty($tracks) ? $tracks[0]->getCarrierCode() : '';
                    }elseif ($property == 'shipment_id') {
                        $tempaletVeribleDetails[$key] = $shipment->getEntityId();
                    } elseif (method_exists($shipment, $methodName)) {
                        $tempaletVeribleDetails[$key] = $shipment->$methodName();
                    } elseif ($shipment->getData($property) !== null) {
                        $tempaletVeribleDetails[$key] = $shipment->getData($property);
                    } elseif (method_exists($order, $methodName)) {
                        $tempaletVeribleDetails[$key] = $order->$methodName();
                    } elseif ($order->getData($property) !== null) {
                        $tempaletVeribleDetails[$key] = $order->getData($property);
                    } else {
                        $tempaletVeribleDetails[$key] = '';
                    }
                }
                
                // foreach ($tempaletVeribleData as $value) {
                //     $key = $value["order"];
                //     $property = $value['limit'];
                //     $methodName = 'get' . str_replace('_', '', ucwords($property, '_'));
                //     if (method_exists($order, $methodName)) {
                //         $tempaletVeribleDetails[$key] = $order->$methodName();
                //     } else {
                //         $tempaletVeribleDetails[$key] = $order->getData($property);
                //     }
                // }

                // $customerData = $this->apiHelper->getCustomerDetails($order, $shipmentTempaletId);
                // $customerId = $this->apiHelper->getContactId($customerData);
                $userDetail = $this->apiHelper->getUserDetailData($order);
                $response = $this->apiHelper->sendMessage($shipmentTempaletId, $tempaletVeribleDetails, 'CreateShippedAfter', $userDetail);
            }
        } catch (\Exception $e) {
            $this->logger->addErrorLog("Error in CreateShippedAfter Observer: " . $e->getMessage());
        } 
    }
}
