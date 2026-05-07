<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Observer;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppNotificationService;
use Azguards\WhatsAppConnect\Service\VariableResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CreateOrderAfter implements ObserverInterface
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
     * Handle the order creation event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $this->logger->info('CreateOrderAfter observer invoked.');
            $order = $observer->getEvent()->getOrder();
            if (!$order || !$order->getEntityId()) {
                $this->logger->warning('CreateOrderAfter observer invoked without a persisted order instance.');
                return;
            }

            $this->logger->info(sprintf(
                'CreateOrderAfter processing order. order_id=%s increment_id=%s customer_email=%s state=%s status=%s',
                (string)$order->getEntityId(),
                (string)$order->getIncrementId(),
                (string)$order->getCustomerEmail(),
                (string)$order->getState(),
                (string)$order->getStatus()
            ));

            $response = $this->notificationService->notifyOrderCreated($order);

            $this->logger->info(sprintf(
                'CreateOrderAfter notifyOrderCreated completed. order_id=%s success=%s message=%s',
                (string)$order->getEntityId(),
                !empty($response['success']) ? 'true' : 'false',
                (string)($response['message'] ?? '')
            ));
        } catch (\Throwable $e) {
            $this->eventLogger->logError(EventConfig::ORDER_CREATION, $e->getMessage());
            $this->logger->error('Error in CreateOrderAfter Observer: ' . $e->getMessage());
        }
    }
}
