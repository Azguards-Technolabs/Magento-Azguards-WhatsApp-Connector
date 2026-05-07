<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Observer;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppNotificationService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as ShipmentTrackCollectionFactory;

class ShipmentTrackSaved implements ObserverInterface
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
     * @var ShipmentTrackCollectionFactory
     */
    private ShipmentTrackCollectionFactory $shipmentTrackCollectionFactory;

    /**
     * @param WhatsAppNotificationService $notificationService
     * @param WhatsAppEventLogger $eventLogger
     * @param Logger $logger
     * @param ShipmentTrackCollectionFactory $shipmentTrackCollectionFactory
     */
    public function __construct(
        WhatsAppNotificationService $notificationService,
        WhatsAppEventLogger $eventLogger,
        Logger $logger,
        ShipmentTrackCollectionFactory $shipmentTrackCollectionFactory
    ) {
        $this->notificationService = $notificationService;
        $this->eventLogger = $eventLogger;
        $this->logger = $logger;
        $this->shipmentTrackCollectionFactory = $shipmentTrackCollectionFactory;
    }

    /**
     * Send shipment notification after a shipment track has been persisted.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var Track|null $track */
            $track = $observer->getEvent()->getTrack();
            if (!$track || !$track->getEntityId() || !$track->getParentId()) {
                $this->logger->warning('ShipmentTrackSaved observer invoked without a persisted track instance.');
                return;
            }

            $shipment = $track->getShipment();
            if (!$shipment || !$shipment->getEntityId()) {
                $this->logger->warning(sprintf(
                    'ShipmentTrackSaved could not resolve shipment for track_id=%s parent_id=%s',
                    (string)$track->getEntityId(),
                    (string)$track->getParentId()
                ));
                return;
            }

            $trackCollection = $this->shipmentTrackCollectionFactory->create();
            $trackCollection->setShipmentFilter((int)$shipment->getEntityId());
            $trackCollection->setOrder('entity_id', 'ASC');

            $firstTrack = $trackCollection->getFirstItem();
            $trackIds = $trackCollection->getColumnValues('entity_id');

            $this->logger->info(sprintf(
                'ShipmentTrackSaved processing track. shipment_id=%s track_id=%s first_track_id=%s track_count=%d track_number=%s carrier=%s',
                (string)$shipment->getEntityId(),
                (string)$track->getEntityId(),
                (string)$firstTrack->getId(),
                count($trackIds),
                (string)$track->getTrackNumber(),
                (string)($track->getTitle() ?: $track->getCarrierCode())
            ));

            if ((int)$firstTrack->getId() !== (int)$track->getEntityId()) {
                $this->logger->info(sprintf(
                    'ShipmentTrackSaved skipped duplicate shipment notification. shipment_id=%s track_id=%s first_track_id=%s',
                    (string)$shipment->getEntityId(),
                    (string)$track->getEntityId(),
                    (string)$firstTrack->getId()
                ));
                return;
            }

            $response = $this->notificationService->notifyShipmentCreated($shipment);

            $this->logger->info(sprintf(
                'ShipmentTrackSaved notifyShipmentCreated completed. shipment_id=%s track_id=%s success=%s message=%s',
                (string)$shipment->getEntityId(),
                (string)$track->getEntityId(),
                !empty($response['success']) ? 'true' : 'false',
                (string)($response['message'] ?? '')
            ));
        } catch (\Throwable $e) {
            $this->eventLogger->logError(EventConfig::ORDER_SHIPMENT, $e->getMessage(), [
                'observer' => self::class,
            ]);
            $this->logger->error('Error in ShipmentTrackSaved Observer: ' . $e->getMessage());
        }
    }
}
