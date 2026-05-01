<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Observer;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppNotificationService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderShipped implements ObserverInterface
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
     * Handle the shipment creation event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $this->logger->info('OrderShipped observer invoked.');
            $shipment = $observer->getEvent()->getShipment();
            if (!$shipment || !$shipment->getEntityId()) {
                $this->logger->warning('OrderShipped observer invoked without a persisted shipment instance.');
                return;
            }

            $this->logger->info(sprintf(
                'OrderShipped processing shipment. shipment_id=%s order_id=%s',
                (string)$shipment->getEntityId(),
                (string)$shipment->getOrderId()
            ));

            $response = $this->notificationService->notifyShipmentCreated($shipment);

            $this->logger->info(sprintf(
                'OrderShipped notifyShipmentCreated completed. shipment_id=%s success=%s message=%s',
                (string)$shipment->getEntityId(),
                !empty($response['success']) ? 'true' : 'false',
                (string)($response['message'] ?? '')
            ));
        } catch (\Throwable $e) {
            $this->eventLogger->logError(EventConfig::ORDER_SHIPMENT, $e->getMessage());
            $this->logger->error('Error in OrderShipped Observer: ' . $e->getMessage());
        }
    }
}
