<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;

class OrderFullInvoicePaid implements ObserverInterface
{
    public const XML_PATH_SEARCHABLE_DROPDOWN_ORDER_INVOICE =
    "whatsApp_conector/order_invoice/searchable_dropdown_order_invoice";
    public const XML_PATH_ORDER_INVOICE_VERIABLE =
    "whatsApp_conector/order_invoice/order_invoice_variable";
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
     * OrderFullInvoicePaid construct
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
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();
            $invoiceTempaletId = $this->apiHelper->getConfigValue(
                self::XML_PATH_SEARCHABLE_DROPDOWN_ORDER_INVOICE
            );
            $invoiceTempaletVerible = $this->apiHelper->getConfigValue(
                self::XML_PATH_ORDER_INVOICE_VERIABLE
            );
            $enable = $this->apiHelper->getConfigValue(self::XML_PATH_ENABLE_MODULES);
            if ($invoiceTempaletId && $enable) {
                $tempaletVeribleData = json_decode($invoiceTempaletVerible, true);
                $tempaletVeribleDetails = [];

                foreach ($tempaletVeribleData as $value) {
                    $key = $value["order"];
                    $property = $value['limit'];
                    $methodName = 'get' . str_replace('_', '', ucwords($property, '_'));

                    if ($property == 'invoice_id') {
                        $tempaletVeribleDetails[$key] = $invoice->getEntityId();
                    } elseif (method_exists($invoice, $methodName)) {
                        $tempaletVeribleDetails[$key] = $invoice->$methodName();
                    } elseif (method_exists($order, $methodName)) {
                        $tempaletVeribleDetails[$key] = $order->$methodName();
                    } elseif ($invoice->getData($property) !== null) {
                        $tempaletVeribleDetails[$key] = $invoice->getData($property);
                    } elseif ($order->getData($property) !== null) {
                        $tempaletVeribleDetails[$key] = $order->getData($property);
                    } else {
                        $tempaletVeribleDetails[$key] = ''; // fallback
                    }
                }

                $userDetail = $this->apiHelper->getUserDetailData($order);
                $response = $this->apiHelper->sendMessage(
                    $invoiceTempaletId,
                    $tempaletVeribleDetails,
                    'OrderFullInvoicePaid',
                    $userDetail
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in OrderFullInvoicePaid Observer: " . $e->getMessage());
        }
    }
}
