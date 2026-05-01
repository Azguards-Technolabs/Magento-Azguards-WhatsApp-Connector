<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Quote\Api\Data\CartInterface;

class WhatsAppNotificationService
{
    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var EventConfig
     */
    private EventConfig $eventConfig;

    /**
     * @var TemplateVariableResolver
     */
    private TemplateVariableResolver $templateVariableResolver;

    /**
     * @var CustomerDataBuilder
     */
    private CustomerDataBuilder $customerDataBuilder;

    /**
     * @var WhatsAppEventLogger
     */
    private WhatsAppEventLogger $eventLogger;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var TemplateCollectionFactory
     */
    private TemplateCollectionFactory $templateCollectionFactory;

    /**
     * @param ApiHelper $apiHelper
     * @param EventConfig $eventConfig
     * @param TemplateVariableResolver $templateVariableResolver
     * @param CustomerDataBuilder $customerDataBuilder
     * @param WhatsAppEventLogger $eventLogger
     * @param Json $json
     * @param Logger $logger
     * @param TemplateCollectionFactory $templateCollectionFactory
     */
    public function __construct(
        ApiHelper $apiHelper,
        EventConfig $eventConfig,
        TemplateVariableResolver $templateVariableResolver,
        CustomerDataBuilder $customerDataBuilder,
        WhatsAppEventLogger $eventLogger,
        Json $json,
        Logger $logger,
        TemplateCollectionFactory $templateCollectionFactory
    ) {
        $this->apiHelper = $apiHelper;
        $this->eventConfig = $eventConfig;
        $this->templateVariableResolver = $templateVariableResolver;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->eventLogger = $eventLogger;
        $this->json = $json;
        $this->logger = $logger;
        $this->templateCollectionFactory = $templateCollectionFactory;
    }

    /**
     * Notify a newly registered customer.
     *
     * @param CustomerInterface $customer
     * @param array|null $userDetailOverride
     * @return array
     */
    public function notifyCustomerRegistration($customer, ?array $userDetailOverride = null): array
    {
        $this->logger->info(sprintf(
            'notifyCustomerRegistration called. customer_id=%s email=%s',
            (string)$customer->getEntityId(),
            (string)$customer->getEmail()
        ));

        try {
            $userDetail = is_array($userDetailOverride)
                ? $userDetailOverride
                : $this->customerDataBuilder->buildFromCustomer($customer);
            $this->logger->info('notifyCustomerRegistration - User detail built.');

            return $this->notify(
                EventConfig::CUSTOMER_REGISTRATION,
                [$customer],
                $userDetail
            );
        } catch (\Exception $e) {
            $this->logger->error('Error in notifyCustomerRegistration: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Notify an order creation event.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function notifyOrderCreated(OrderInterface $order): array
    {
        $this->logger->info(sprintf(
            'notifyOrderCreated called. order_id=%s increment_id=%s customer_email=%s',
            (string)$order->getEntityId(),
            (string)$order->getIncrementId(),
            (string)$order->getCustomerEmail()
        ));

        return $this->notify(
            EventConfig::ORDER_CREATION,
            [$order, $order->getBillingAddress(), $order->getShippingAddress()],
            $this->customerDataBuilder->buildFromOrder($order)
        );
    }

    /**
     * Notify a paid invoice event.
     *
     * @param InvoiceInterface $invoice
     * @return array
     */
    public function notifyInvoiceCreated(InvoiceInterface $invoice): array
    {
        $order = $invoice->getOrder();

        return $this->notify(
            EventConfig::ORDER_INVOICE,
            [$invoice, $order, $invoice->getBillingAddress(), $order ? $order->getBillingAddress() : null],
            $order ? $this->customerDataBuilder->buildFromOrder($order) : []
        );
    }

    /**
     * Notify a shipment creation event.
     *
     * @param ShipmentInterface $shipment
     * @return array
     */
    public function notifyShipmentCreated(ShipmentInterface $shipment): array
    {
        $order = $shipment->getOrder();
        $tracks = $shipment->getAllTracks();

        return $this->notify(
            EventConfig::ORDER_SHIPMENT,
            [
                $shipment,
                $order,
                ['tracks' => array_values($tracks)],
                $shipment->getShippingAddress(),
                $order ? $order->getShippingAddress() : null,
            ],
            $order ? $this->customerDataBuilder->buildFromOrder($order) : []
        );
    }

    /**
     * Notify an order cancellation event.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function notifyOrderCancelled(OrderInterface $order): array
    {
        return $this->notify(
            EventConfig::ORDER_CANCELLATION,
            [$order, $order->getBillingAddress(), $order->getShippingAddress()],
            $this->customerDataBuilder->buildFromOrder($order)
        );
    }

    /**
     * Notify a credit memo creation event.
     *
     * @param CreditmemoInterface $creditmemo
     * @return array
     */
    public function notifyCreditMemoCreated(CreditmemoInterface $creditmemo): array
    {
        $order = $creditmemo->getOrder();

        return $this->notify(
            EventConfig::ORDER_CREDIT_MEMO,
            [$creditmemo, $order, $creditmemo->getBillingAddress(), $order ? $order->getBillingAddress() : null],
            $order ? $this->customerDataBuilder->buildFromOrder($order) : []
        );
    }

    /**
     * Notify an abandoned cart event.
     *
     * @param CartInterface $quote
     * @return array
     */
    public function notifyAbandonedCart(CartInterface $quote): array
    {
        return $this->notify(
            EventConfig::ABANDON_CART,
            [$quote, $quote->getBillingAddress(), $quote->getShippingAddress()],
            $this->customerDataBuilder->buildFromQuote($quote)
        );
    }

    /**
     * Build and send a WhatsApp notification for the given event.
     *
     * @param string $eventCode
     * @param array $contexts
     * @param array $userDetail
     * @return array
     */
    private function notify(string $eventCode, array $contexts, array $userDetail): array
    {
        $this->logger->info(sprintf(
            'WhatsApp notify start. event=%s context_count=%d has_user_detail=%s',
            $eventCode,
            count(array_filter($contexts)),
            !empty($userDetail) ? 'true' : 'false'
        ));

        $this->eventLogger->logEventTriggered($eventCode, [
            'context_count' => count(array_filter($contexts)),
            'has_user_detail' => !empty($userDetail),
        ]);

        if (!(bool)$this->apiHelper->getConfigValue(EventConfig::MODULE_ENABLED)) {
            $this->logger->warning(
                sprintf('WhatsApp notify skipped. event=%s reason=module_disabled', $eventCode)
            );
            return ['success' => false, 'message' => 'WhatsApp connector disabled'];
        }

        $config = $this->eventConfig->get($eventCode);
        if ($config === []) {
            $this->logger->warning(
                sprintf('WhatsApp notify skipped. event=%s reason=missing_event_config', $eventCode)
            );
            return ['success' => false, 'message' => 'Missing event configuration'];
        }

        $templateId = (string)$this->apiHelper->getConfigValue($config['template']);
        if ($templateId === '') {
            $this->logger->warning(
                sprintf('WhatsApp notify skipped. event=%s reason=template_not_configured', $eventCode)
            );
            return ['success' => false, 'message' => 'Template not configured'];
        }

        if (empty($userDetail['mobileNumber']) || empty($userDetail['countryCode'])) {
            $this->logger->warning(
                sprintf('WhatsApp notify skipped. event=%s reason=missing_phone', $eventCode)
            );
            return [
                'success' => false,
                'message' => 'Mobile number or country code missing',
                'template_id' => $templateId,
            ];
        }

        $variableMap = $this->readVariableMap((string)$this->apiHelper->getConfigValue($config['variables']));
        $placeholders = $this->templateVariableResolver->resolve($variableMap, array_filter($contexts));

        $this->eventLogger->logPayload($eventCode, [
            'template_id' => $templateId,
            'placeholder_values' => $placeholders,
            'user_detail' => $userDetail,
        ], [
            'request_type' => (string)$config['request_type'],
            'variable_map_count' => count($variableMap),
        ]);

        $mediaHandle = $this->resolveEventMediaHandle($config, $templateId);

        $response = $this->apiHelper->sendTemplateMessage(
            $templateId,
            $placeholders,
            $userDetail,
            (string)$config['request_type'],
            $mediaHandle ?: null,
            null,
            (bool)($config['sync_contact'] ?? true)
        );

        // Provide template id to upstream callers (cron/monitoring) without modifying API response semantics.
        $response['template_id'] = $templateId;

        $this->eventLogger->logApiResponse($eventCode, $response, [
            'template_id' => $templateId,
            'request_type' => (string)$config['request_type'],
        ]);

        if (!($response['success'] ?? false)) {
            $this->eventLogger->logError($eventCode, (string)($response['message'] ?? 'Unknown error'), [
                'template_id' => $templateId,
                'request_type' => (string)$config['request_type'],
            ]);
            $this->logger->warning(sprintf(
                'WhatsApp notification failed for %s: %s',
                $eventCode,
                (string)($response['message'] ?? 'Unknown error')
            ));
        }

        $this->logger->info(sprintf(
            'WhatsApp notify completed. event=%s template_id=%s success=%s message=%s',
            $eventCode,
            $templateId,
            !empty($response['success']) ? 'true' : 'false',
            (string)($response['message'] ?? '')
        ));

        return $response;
    }

    /**
     * Decode the configured variable mapping.
     *
     * @param string $rawConfig
     * @return array
     */
    private function readVariableMap(string $rawConfig): array
    {
        if ($rawConfig === '') {
            return [];
        }

        try {
            $decoded = $this->json->unserialize($rawConfig);
            return is_array($decoded) ? $decoded : [];
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error('Unable to decode WhatsApp variable mapping: ' . $exception->getMessage());
            return [];
        }
    }

    /**
     * Resolve the media handle that should be sent for the event.
     *
     * @param array $config
     * @param string $templateId
     * @return string
     */
    private function resolveEventMediaHandle(array $config, string $templateId): string
    {
        $configPath = (string)($config['media_handle'] ?? '');
        $configuredHandle = $configPath !== '' ? (string)$this->apiHelper->getConfigValue($configPath) : '';

        try {
            $collection = $this->templateCollectionFactory->create();
            $collection->addFieldToFilter('template_id', $templateId);
            $template = $collection->getFirstItem();
            $headerFormat = strtoupper((string)$template->getData('header_format'));
            $templateHandle = (string)$template->getData('header_handle');

            // Only include media when template header expects it.
            if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                return $configuredHandle !== '' ? $configuredHandle : $templateHandle;
            }
        } catch (\Exception $e) {
            $this->logger->warning('resolveEventMediaHandle failed: ' . $e->getMessage(), [
                'template_id' => $templateId
            ]);
        }

        return '';
    }
}
