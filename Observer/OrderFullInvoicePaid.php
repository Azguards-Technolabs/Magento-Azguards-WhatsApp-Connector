<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Observer;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppNotificationService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderFullInvoicePaid implements ObserverInterface
{
    /**
     * @var WhatsAppNotificationService
     */
    private WhatsAppNotificationService $notificationService;

    /**
     * @var WhatsAppEventLogger
     */
    private WhatsAppEventLogger $eventLogger;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param WhatsAppNotificationService $notificationService
     * @param WhatsAppEventLogger $eventLogger
     * @param Logger $logger
     */
    public function __construct(
        WhatsAppNotificationService $notificationService,
        WhatsAppEventLogger $eventLogger,
        Logger $logger
    ) {
        $this->notificationService = $notificationService;
        $this->eventLogger = $eventLogger;
        $this->logger = $logger;
    }

    /**
     * Handle the paid invoice event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $this->logger->info('OrderFullInvoicePaid observer invoked.');
            $invoice = $observer->getEvent()->getInvoice();
            if (!$invoice || !$invoice->getEntityId()) {
                $this->logger->warning('OrderFullInvoicePaid observer invoked without a persisted invoice instance.');
                return;
            }

            $this->logger->info(sprintf(
                'OrderFullInvoicePaid processing invoice. invoice_id=%s order_id=%s ',
                (string)$invoice->getEntityId(),
                (string)$invoice->getOrderId()
            ));

            $response = $this->notificationService->notifyInvoiceCreated($invoice);

            $this->logger->info(sprintf(
                'OrderFullInvoicePaid notifyInvoiceCreated completed. invoice_id=%s success=%s message=%s',
                (string)$invoice->getEntityId(),
                !empty($response['success']) ? 'true' : 'false',
                (string)($response['message'] ?? '')
            ));
        } catch (\Throwable $e) {
            $this->eventLogger->logError(EventConfig::ORDER_INVOICE, $e->getMessage());
            $this->logger->error('Error in OrderFullInvoicePaid Observer: ' . $e->getMessage());
        }
    }
}
