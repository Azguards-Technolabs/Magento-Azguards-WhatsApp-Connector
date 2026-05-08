<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Service for sending WhatsApp notifications.
 */
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
     * @var \Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig
     */
    private \Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig $templateConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param ApiHelper $apiHelper
     * @param EventConfig $eventConfig
     * @param TemplateVariableResolver $templateVariableResolver
     * @param CustomerDataBuilder $customerDataBuilder
     * @param WhatsAppEventLogger $eventLogger
     * @param Json $json
     * @param Logger $logger
     * @param TemplateCollectionFactory $templateCollectionFactory
     * @param \Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig $templateConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ApiHelper $apiHelper,
        EventConfig $eventConfig,
        TemplateVariableResolver $templateVariableResolver,
        CustomerDataBuilder $customerDataBuilder,
        WhatsAppEventLogger $eventLogger,
        Json $json,
        Logger $logger,
        TemplateCollectionFactory $templateCollectionFactory,
        \Azguards\WhatsAppConnect\Model\Config\WhatsAppTemplateConfig $templateConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->apiHelper = $apiHelper;
        $this->eventConfig = $eventConfig;
        $this->templateVariableResolver = $templateVariableResolver;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->eventLogger = $eventLogger;
        $this->json = $json;
        $this->logger = $logger;
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->templateConfig = $templateConfig;
        $this->storeManager = $storeManager;
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

            $contexts = [$customer];
            if ($customer instanceof CustomerInterface) {
                $billingId = $customer->getDefaultBilling();

                foreach ($customer->getAddresses() ?: [] as $address) {
                    $contexts[] = $address;
                    if ($address->getId() && $address->getId() == $billingId) {
                        $contexts[] = $address; // Add again as primary context
                    }
                }
            }

            return $this->notify(
                EventConfig::CUSTOMER_REGISTRATION,
                $contexts,
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
        $contexts = [$quote];
        if ($quote->getBillingAddress()) {
            $contexts[] = $quote->getBillingAddress();
        }
        if ($quote->getShippingAddress()) {
            $contexts[] = $quote->getShippingAddress();
        }
        foreach ($quote->getAllVisibleItems() as $item) {
            $contexts[] = $item;
        }

        return $this->notify(
            EventConfig::ABANDON_CART,
            $contexts,
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

        $storeId = $this->resolveStoreId($contexts);

        // Ensure store context is present for variable resolution
        $store = $this->storeManager->getStore($storeId ?: null);
        $contexts[] = $store;

        if (!(bool)$this->templateConfig->getByXmlPath(EventConfig::MODULE_ENABLED, $storeId)) {
            $this->logger->warning(
                sprintf('WhatsApp notify skipped. event=%s reason=module_disabled store=%d', $eventCode, $storeId)
            );
            return ['success' => false, 'message' => 'WhatsApp connector disabled'];
        }

        $eventConfig = $this->eventConfig->get($eventCode);
        if ($eventConfig === []) {
            $this->logger->warning(
                sprintf('WhatsApp notify skipped. event=%s reason=missing_event_config', $eventCode)
            );
            return ['success' => false, 'message' => 'Missing event configuration'];
        }

        // Check if event-specific notification is enabled
        if (isset($eventConfig['enable_field'])) {
            $enablePath = 'whatsApp_conector/general/' . $eventConfig['enable_field'];
            if (!$this->templateConfig->getByXmlPath($enablePath, $storeId)) {
                $this->logger->warning(
                    sprintf('WhatsApp notify skipped. event=%s reason=event_disabled store=%d', $eventCode, $storeId)
                );
                return ['success' => false, 'message' => 'WhatsApp event disabled'];
            }
        }

        // Try Builder Configuration First
        if (isset($eventConfig['builder_group'])) {
            $builderConfigPath = $this->templateConfig->getGroupXmlPath($eventConfig['builder_group']);
            $bodyTemplate = (string)$this->templateConfig
                ->getByXmlPath($builderConfigPath . '/body_template', $storeId);

            if ($bodyTemplate !== '') {
                return $this->notifyViaBuilder(
                    $eventCode,
                    $eventConfig,
                    $builderConfigPath,
                    $contexts,
                    $userDetail,
                    (int)$storeId
                );
            }
        }

        // Fallback to legacy
        $templateId = (string)$this->templateConfig->getByXmlPath($eventConfig['template'], $storeId);
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

        $vMapRaw = (string)$this->templateConfig->getByXmlPath($eventConfig['variables'], $storeId);
        $variableMap = $this->readVariableMap($vMapRaw);
        $placeholders = $this->templateVariableResolver->resolve($variableMap, array_filter($contexts));

        $this->eventLogger->logPayload($eventCode, [
            'template_id' => $templateId,
            'placeholder_values' => $placeholders,
            'user_detail' => $userDetail,
        ], [
            'request_type' => (string)$eventConfig['request_type'],
            'variable_map_count' => count($variableMap),
        ]);

        $mediaHandle = $this->resolveEventMediaHandle($eventConfig, $templateId, (int)$storeId);

        $response = $this->apiHelper->sendTemplateMessage(
            $templateId,
            $placeholders,
            $userDetail,
            (string)$eventConfig['request_type'],
            $mediaHandle ?: null,
            null,
            (bool)($eventConfig['sync_contact'] ?? true)
        );

        // Provide template id to upstream callers (cron/monitoring) without modifying API response semantics.
        $response['template_id'] = $templateId;

        $this->eventLogger->logApiResponse($eventCode, $response, [
            'template_id' => $templateId,
            'request_type' => (string)$eventConfig['request_type'],
        ]);

        if (!($response['success'] ?? false)) {
            $this->eventLogger->logError($eventCode, (string)($response['message'] ?? 'Unknown error'), [
                'template_id' => $templateId,
                'request_type' => (string)$eventConfig['request_type'],
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
     * Resolve store ID from context objects.
     *
     * @param array $contexts
     * @return int
     */
    private function resolveStoreId(array $contexts): int
    {
        foreach ($contexts as $ctx) {
            if ($ctx && method_exists($ctx, 'getStoreId')) {
                return (int)$ctx->getStoreId();
            }
        }
        return 0;
    }

    /**
     * Send notification using modern Builder configuration.
     *
     * @param string $eventCode
     * @param array $eventConfig
     * @param string $builderConfigPath
     * @param array $contexts
     * @param array $userDetail
     * @param int $storeId
     * @return array
     */
    private function notifyViaBuilder(
        string $eventCode,
        array $eventConfig,
        string $builderConfigPath,
        array $contexts,
        array $userDetail,
        int $storeId
    ): array {
        $templateName = (string)$this->templateConfig->getByXmlPath($builderConfigPath . '/template_name', $storeId);
        if ($templateName === '') {
            return ['success' => false, 'message' => 'Builder template name not configured'];
        }

        // Look up template_id by name
        $collection = $this->templateCollectionFactory->create();
        $collection->addFieldToFilter('template_name', $templateName);
        $template = $collection->getFirstItem();
        $templateId = (string)$template->getTemplateId();

        if ($templateId === '') {
            return ['success' => false, 'message' => 'Meta template ID not found for: ' . $templateName];
        }

        $bodyTemplate = (string)$this->templateConfig->getByXmlPath($builderConfigPath . '/body_template', $storeId);

        // Senior Level: Pre-process items loop into a single string summary
        $itemsSummary = $this->resolveItemsSummary($bodyTemplate, $contexts);
        if ($itemsSummary !== '') {
            $bodyTemplate = (string)preg_replace(
                '/\{\{\#items\}\}([\s\S]*?)\{\{\/items\}\}/',
                '{{items_summary}}',
                $bodyTemplate
            );
        }

        // Extract placeholders using Senior variable resolver
        $placeholders = $this->resolveVariables($bodyTemplate, $contexts, $itemsSummary);

        // Resolve Button Variables - Grouped by button index for API helper
        $buttonsData = $this->resolveButtonsData($builderConfigPath, $template, $contexts, $itemsSummary, $storeId);

        $this->eventLogger->logPayload($eventCode . '_builder', [
            'template_name' => $templateName,
            'template_id' => $templateId,
            'placeholders' => $placeholders,
            'user_detail' => $userDetail,
        ]);

        // Add userDetail to contexts for deep resolution
        $contexts[] = $userDetail;

        $mediaHandle = (string)$this->templateConfig->getByXmlPath($builderConfigPath . '/header_handle', $storeId);
        if ($mediaHandle === '') {
            $mediaHandle = (string)$template->getHeaderHandle();
        }

        $response = $this->apiHelper->sendTemplateMessage(
            $templateId,
            $placeholders,
            $userDetail,
            (string)$eventConfig['request_type'],
            $mediaHandle ?: null,
            null,
            (bool)($eventConfig['sync_contact'] ?? true),
            $buttonsData
        );

        $this->logger->info(sprintf(
            'WhatsApp Builder notify completed. event=%s template=%s success=%s',
            $eventCode,
            $templateName,
            !empty($response['success']) ? 'true' : 'false'
        ));

        return $response;
    }

    /**
     * Resolve items summary for loop variables.
     *
     * @param string $bodyTemplate
     * @param array $contexts
     * @return string
     */
    private function resolveItemsSummary(string $bodyTemplate, array $contexts): string
    {
        $loopMatch = [];
        if (!preg_match('/\{\{\#items\}\}([\s\S]*?)\{\{\/items\}\}/', $bodyTemplate, $loopMatch)) {
            return '';
        }

        $itemRowTemplate = $loopMatch[1];
        $summaryRows = [];
        foreach ($contexts as $ctx) {
            if ($ctx instanceof \Magento\Quote\Api\Data\CartItemInterface ||
                $ctx instanceof \Magento\Sales\Api\Data\OrderItemInterface
            ) {
                $row = $itemRowTemplate;
                preg_match_all('/\{\{\s*(?:var\s+)?(.*?)\s*\}\}/', $row, $rowMatches);

                if (!empty($rowMatches[1])) {
                    foreach ($rowMatches[1] as $rvPath) {
                        $val = $this->resolveGenericContext($rvPath, $ctx);
                        $row = str_replace(['{{var ' . $rvPath . '}}', '{{' . $rvPath . '}}'], $val, $row);
                    }
                }
                $summaryRows[] = trim($row);
            }
        }
        return implode("\n", $summaryRows);
    }

    /**
     * Resolve variables from text.
     *
     * @param string $text
     * @param array $contexts
     * @param string $itemsSummary
     * @param bool $isButton
     * @return array
     */
    private function resolveVariables(
        string $text,
        array $contexts,
        string $itemsSummary,
        bool $isButton = false
    ): array {
        $found = [];
        preg_match_all('/\{\{\s*(?:var\s+)?(.*?)\s*\}\}/', $text, $matches);
        if (empty($matches[1])) {
            return [];
        }

        foreach ($matches[1] as $varPath) {
            $prop = $varPath;
            if (str_contains($prop, '.')) {
                $parts = explode('.', $prop);
                $prop = end($parts);
            }
            $prop = str_replace('()', '', $prop);
            $cleanVarName = preg_replace('/[^a-zA-Z0-9_]/', '', $prop);

            if ($cleanVarName === 'items_summary' && !$isButton) {
                $found[$cleanVarName] = $itemsSummary;
                continue;
            }

            // Resolve against all available contexts
            $resolvedValue = '';
            foreach ($contexts as $ctx) {
                $resolvedValue = $this->resolveGenericContext($varPath, $ctx);
                if ($resolvedValue !== '') {
                    break;
                }
            }
            $found[$cleanVarName] = $resolvedValue;
        }
        return $found;
    }

    /**
     * Resolve buttons data for notification.
     *
     * @param string $builderConfigPath
     * @param mixed $template
     * @param array $contexts
     * @param string $itemsSummary
     * @param int $storeId
     * @return array
     */
    private function resolveButtonsData(
        string $builderConfigPath,
        $template,
        array $contexts,
        string $itemsSummary,
        int $storeId
    ): array {
        $buttonsData = [];
        $buttonsJson = (string)$this->templateConfig->getByXmlPath($builderConfigPath . '/buttons_json', $storeId);
    
        if ($buttonsJson === '') {
            $buttonsJson = (string)$template->getButtons();
        }

        if ($buttonsJson === '') {
            return $buttonsData;
        }

        $buttons = $this->json->unserialize($buttonsJson);
        if (!is_array($buttons)) {
            return $buttonsData;
        }

        foreach ($buttons as $index => $button) {
            $urlVal = $button['button_url'] ?? $button['url'] ?? $button['value'] ?? '';
        
            if (($button['type'] ?? '') !== 'URL' || $urlVal === '') {
                continue;
            }

            $resolved = $this->resolveVariables((string)$urlVal, $contexts, $itemsSummary, true);
            if (empty($resolved)) {
                continue;
            }

            $buttonsData[] = [
            'index' => $index,
            'placeholders' => $resolved
            ];
        }

        return $buttonsData;
    }

    /**
     * Resolve a variable path against a generic context object.
     *
     * @param string $varPath
     * @param mixed $context
     * @return string
     */
    private function resolveGenericContext(string $varPath, $context): string
    {
        $prefix = explode('.', $varPath)[0] ?? '';

        // Match prefix to context type or array key
        if (!$this->isMatchingContext($prefix, $context)) {
            return '';
        }

        try {
            // Strip the prefix (e.g. "order.") before resolving against the specific context
            $pathWithoutPrefix = $varPath;
            if (strpos($varPath, '.') !== false) {
                $pathWithoutPrefix = substr($varPath, strpos($varPath, '.') + 1);
            }

            // Use TemplateVariableResolver to extract value since it handles generic objects/arrays
            return (string)$this->templateVariableResolver->resolveValue($pathWithoutPrefix, [$context]);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if the prefix matches the context type.
     *
     * @param string $prefix
     * @param mixed $context
     * @return bool
     */
    private function isMatchingContext(string $prefix, $context): bool
    {
        $map = [
            'order' => OrderInterface::class,
            'invoice' => InvoiceInterface::class,
            'shipment' => ShipmentInterface::class,
            'creditmemo' => CreditmemoInterface::class,
            'customer' => CustomerInterface::class,
            'address' => \Magento\Customer\Api\Data\AddressInterface::class,
            'quote' => CartInterface::class,
            'store' => \Magento\Store\Api\Data\StoreInterface::class,
        ];

        if (isset($map[$prefix]) && ($context instanceof $map[$prefix] ||
            (is_array($context) && isset($context[$prefix])))) {
            return true;
        }

        if ($prefix === 'billing' && $context instanceof \Magento\Quote\Api\Data\AddressInterface &&
            $context->getAddressType() === 'billing') {
            return true;
        }
        if ($prefix === 'shipping' && $context instanceof \Magento\Quote\Api\Data\AddressInterface &&
            $context->getAddressType() === 'shipping') {
            return true;
        }
        if ($prefix === 'items' && ($context instanceof \Magento\Quote\Api\Data\CartItemInterface ||
            $context instanceof \Magento\Sales\Api\Data\OrderItemInterface)) {
            return true;
        }

        return false;
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
     * @param int $storeId
     * @return string
     */
    private function resolveEventMediaHandle(array $config, string $templateId, int $storeId): string
    {
        $configPath = (string)($config['media_handle'] ?? '');
        $configuredHandle = $configPath !== '' ? (string)$this->templateConfig
        ->getByXmlPath($configPath, $storeId) : '';

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
