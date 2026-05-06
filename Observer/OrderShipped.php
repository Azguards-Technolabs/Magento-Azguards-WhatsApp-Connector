<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Observer;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Service\VariableResolver;
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
     * @var WhatsAppTemplateConfig
     */
    private WhatsAppTemplateConfig $templateConfig;

    /**
     * @var VariableResolver
     */
    private VariableResolver $variableResolver;

    /**
     * @param WhatsAppNotificationService $notificationService
     * @param WhatsAppEventLogger $eventLogger
     * @param Logger $logger
     * @param WhatsAppTemplateConfig $templateConfig
     * @param VariableResolver $variableResolver
     */
    public function __construct(
        WhatsAppNotificationService $notificationService,
        WhatsAppEventLogger $eventLogger,
        Logger $logger,
        WhatsAppTemplateConfig $templateConfig,
        VariableResolver $variableResolver
    ) {
        $this->notificationService = $notificationService;
        $this->eventLogger = $eventLogger;
        $this->logger = $logger;
        $this->templateConfig = $templateConfig;
        $this->variableResolver = $variableResolver;
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


            $order = $shipment->getOrder();

            if ($this->templateConfig->hasShipmentBodyTemplate((int)$order->getStoreId())) {
                $payload = $this->buildConfiguredTemplatePayload($order);
                $this->eventLogger->logPayload('order_shipment_template_builder', $payload, [
                    'store_id' => (int)$order->getStoreId(),
                    'shipment_id' => (int)$shipment->getEntityId()
                ]);
                $this->logger->info(sprintf(
                    'OrderShipped template payload prepared from system config. order_id=%s template_name=%s',
                    (string)$order->getEntityId(),
                    (string)($payload['template_name'] ?? '')
                ));
                return;
            }

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

    /**
     * Build the configured order_shipment template payload.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @return array
     */
    private function buildConfiguredTemplatePayload($order): array
    {
        $config = $this->templateConfig->getShipmentTemplateConfig((int)$order->getStoreId());
        $resolvedBody = $this->variableResolver->resolve((string)($config['body_template'] ?? ''), $order);
        $resolvedFooter = $this->variableResolver->resolve((string)($config['footer_template'] ?? ''), $order);
        $headerType = (string)($config['header_type'] ?? 'none');
        $headerText = '';

        if ($headerType === 'text') {
            $headerText = $this->variableResolver->resolve((string)($config['header_text'] ?? ''), $order);
        }

        return [
            'event_code' => (string)($config['event_code'] ?? 'order_shipment'),
            'template_name' => (string)($config['template_name'] ?? ''),
            'category' => (string)($config['category'] ?? ''),
            'language' => (string)($config['language'] ?? ''),
            'store_id' => (int)$order->getStoreId(),
            'order_id' => (int)$order->getEntityId(),
            'header' => [
                'type' => $headerType,
                'text' => $headerText,
            ],
            'body_template' => (string)($config['body_template'] ?? ''),
            'body' => $resolvedBody,
            'footer_template' => (string)($config['footer_template'] ?? ''),
            'footer' => $resolvedFooter,
        ];
    }
}