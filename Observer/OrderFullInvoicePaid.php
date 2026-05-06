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


            $order = $invoice->getOrder();

            if ($this->templateConfig->hasInvoiceBodyTemplate((int)$order->getStoreId())) {
                $payload = $this->buildConfiguredTemplatePayload($order);
                $this->eventLogger->logPayload('order_invoice_template_builder', $payload, [
                    'store_id' => (int)$order->getStoreId(),
                    'invoice_id' => (int)$invoice->getEntityId()
                ]);
                $this->logger->info(sprintf(
                    'OrderFullInvoicePaid template payload prepared from system config. order_id=%s template_name=%s',
                    (string)$order->getEntityId(),
                    (string)($payload['template_name'] ?? '')
                ));
                return;
            }

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

    /**
     * Build the configured order_invoice template payload.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @return array
     */
    private function buildConfiguredTemplatePayload($order): array
    {
        $config = $this->templateConfig->getInvoiceTemplateConfig((int)$order->getStoreId());
        $resolvedBody = $this->variableResolver->resolve((string)($config['body_template'] ?? ''), $order);
        $resolvedFooter = $this->variableResolver->resolve((string)($config['footer_template'] ?? ''), $order);
        $headerType = (string)($config['header_type'] ?? 'none');
        $headerText = '';

        if ($headerType === 'text') {
            $headerText = $this->variableResolver->resolve((string)($config['header_text'] ?? ''), $order);
        }

        return [
            'event_code' => (string)($config['event_code'] ?? 'order_invoice'),
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