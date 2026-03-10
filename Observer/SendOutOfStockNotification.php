<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SendOutOfStockNotification implements ObserverInterface
{
    protected $transportBuilder;
    protected $scopeConfig;

    public function __construct(
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer)
    {
        $eventData = $observer->getEvent()->getData();
        
        // Check if 'notify_stock_qty' is available
        if (isset($eventData['notify_stock_qty'])) {
            $notifyStockQty = $eventData['notify_stock_qty'];

            // Log the notify_stock_qty value

            // Now check if stock quantity is 0 (out of stock)
            if ($notifyStockQty <= 0) {
                // Trigger custom notification (API call or email)
            }
        } else {
            // Log if notify_stock_qty is not found
        }
    }
}
