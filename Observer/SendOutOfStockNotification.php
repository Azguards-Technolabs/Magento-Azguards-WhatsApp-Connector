<?php

namespace Azguards\WhatsAppConnect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SendOutOfStockNotification implements ObserverInterface
{
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var ScopeConfig
     */
    protected $scopeConfig;

    /**
     * @var \Azguards\WhatsAppConnect\Logger\Logger
     */
    protected $logger;

    /**
     * SendOutOfStockNotification constructor
     *
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param \Azguards\WhatsAppConnect\Logger\Logger $logger
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        \Azguards\WhatsAppConnect\Logger\Logger $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
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
            $this->logger->info('SendOutOfStockNotification observer invoked.');
            $eventData = $observer->getEvent()->getData();
            
            // Check if 'notify_stock_qty' is available
            if (isset($eventData['notify_stock_qty'])) {
                $notifyStockQty = $eventData['notify_stock_qty'];

                // Log the notify_stock_qty value
                $this->logger->info('SendOutOfStockNotification processing. notify_stock_qty=' . (string)$notifyStockQty);

                // Now check if stock quantity is 0 (out of stock)
                
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in SendOutOfStockNotification Observer: " . $e->getMessage());
        }
    }
}
