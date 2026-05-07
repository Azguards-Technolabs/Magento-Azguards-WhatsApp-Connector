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

class OrderRefund implements ObserverInterface
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
     * Handle the credit memo creation event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $this->logger->info('OrderRefund observer invoked.');
            $creditMemo = $observer->getEvent()->getCreditmemo();
            if (!$creditMemo || !$creditMemo->getEntityId()) {
                $this->logger->warning('OrderRefund observer invoked without a persisted creditMemo instance.');
                return;
            }

            $this->logger->info(sprintf(
                'OrderRefund processing creditMemo. creditmemo_id=%s order_id=%s',
                (string)$creditMemo->getEntityId(),
                (string)$creditMemo->getOrderId()
            ));

            $response = $this->notificationService->notifyCreditMemoCreated($creditMemo);

            $this->logger->info(sprintf(
                'OrderRefund notifyCreditMemoCreated completed. creditmemo_id=%s success=%s message=%s',
                (string)$creditMemo->getEntityId(),
                !empty($response['success']) ? 'true' : 'false',
                (string)($response['message'] ?? '')
            ));
        } catch (\Throwable $e) {
            $this->eventLogger->logError(EventConfig::ORDER_CREDIT_MEMO, $e->getMessage());
            $this->logger->error('Error in OrderRefund Observer: ' . $e->getMessage());
        }
    }
}