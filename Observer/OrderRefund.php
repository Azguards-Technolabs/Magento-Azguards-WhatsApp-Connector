<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;

class OrderRefund implements ObserverInterface
{
    public const XML_PATH_SEARCHABLE_DROPDOWN_ORDER_CREDIT_MEMO =
    "whatsApp_conector/order_credit_memo/searchable_dropdown_order_credit_memo";
    public const XML_PATH_ORDER_CREDIT_MEMO_VERIABLE =
    "whatsApp_conector/order_credit_memo/order_credit_memo_variable";
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
     * OrderRefund construct
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
            $creditMemo = $observer->getEvent()->getCreditmemo();
            $order = $creditMemo->getOrder();
            $creditMemoTempaletId = $this->apiHelper->getConfigValue(
                self::XML_PATH_SEARCHABLE_DROPDOWN_ORDER_CREDIT_MEMO
            );
            $creditMemoTempaletVerible = $this->apiHelper->getConfigValue(
                self::XML_PATH_ORDER_CREDIT_MEMO_VERIABLE
            );
            $enable = $this->apiHelper->getConfigValue(self::XML_PATH_ENABLE_MODULES);
            if ($creditMemoTempaletId && $enable) {
                $tempaletVeribleData = json_decode($creditMemoTempaletVerible, true);
                $tempaletVeribleDetails = [];

                foreach ($tempaletVeribleData as $value) {
                    $key = $value["order"];
                    $property = $value['limit'];
                    $methodName = 'get' . str_replace('_', '', ucwords($property, '_'));

                    if ($property == 'creditmemo_id') {
                        $tempaletVeribleDetails[$key] = $creditMemo->getEntityId();
                    } elseif (method_exists($creditMemo, $methodName)) {
                        $tempaletVeribleDetails[$key] = $creditMemo->$methodName();
                    } elseif (method_exists($order, $methodName)) {
                        $tempaletVeribleDetails[$key] = $order->$methodName();
                    } elseif ($creditMemo->getData($property) !== null) {
                        $tempaletVeribleDetails[$key] = $creditMemo->getData($property);
                    } elseif ($order->getData($property) !== null) {
                        $tempaletVeribleDetails[$key] = $order->getData($property);
                    } else {
                        $tempaletVeribleDetails[$key] = ''; // fallback if nothing found
                    }
                }
                
                $userDetail = $this->apiHelper->getUserDetailData($order);
                $response = $this->apiHelper->sendMessage(
                    $creditMemoTempaletId,
                    $tempaletVeribleDetails,
                    'CreateRefundAfter',
                    $userDetail
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in CreateRefundAfter Observer: " . $e->getMessage());
        }
    }
}
