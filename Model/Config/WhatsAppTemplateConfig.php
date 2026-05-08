<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class WhatsAppTemplateConfig
{
    public const SECTION = 'whatsApp_conector';
    public const GROUP_USER_REGISTRATION_TEMPLATE = 'user_registration_template';
    public const XML_PATH_USER_REGISTRATION_TEMPLATE = self::SECTION . '/' . self::GROUP_USER_REGISTRATION_TEMPLATE;

    public const GROUP_ORDER_TEMPLATE = 'order_template';
    public const XML_PATH_ORDER_TEMPLATE = self::SECTION . '/' . self::GROUP_ORDER_TEMPLATE;

    public const GROUP_ORDER_INVOICE_TEMPLATE = 'order_invoice_template';
    public const XML_PATH_ORDER_INVOICE_TEMPLATE = self::SECTION . '/' . self::GROUP_ORDER_INVOICE_TEMPLATE;

    public const GROUP_ORDER_SHIPMENT_TEMPLATE = 'order_shipment_template';
    public const XML_PATH_ORDER_SHIPMENT_TEMPLATE = self::SECTION . '/' . self::GROUP_ORDER_SHIPMENT_TEMPLATE;

    public const GROUP_ORDER_CANCELLATION_TEMPLATE = 'order_cancellation_template';
    public const XML_PATH_ORDER_CANCELLATION_TEMPLATE = self::SECTION . '/' . self::GROUP_ORDER_CANCELLATION_TEMPLATE;

    public const GROUP_ORDER_CREDIT_MEMO_TEMPLATE = 'order_credit_memo_template';
    public const XML_PATH_ORDER_CREDIT_MEMO_TEMPLATE = self::SECTION . '/' . self::GROUP_ORDER_CREDIT_MEMO_TEMPLATE;

    public const SECTION_ABANDONED_CART = 'whatsapp_abandoned_cart';
    public const GROUP_ABANDONED_CART_TEMPLATE = 'abandoned_cart_template';
    public const XML_PATH_ABANDONED_CART_TEMPLATE = self::SECTION_ABANDONED_CART . '/' . self::GROUP_ABANDONED_CART_TEMPLATE;

    private const DEFAULT_BUTTONS = [
        'order_created' => [
            ['type' => 'URL', 'text' => 'View Order', 'button_url' => 'order_view'],
        ],
        'order_invoice' => [
            ['type' => 'URL', 'text' => 'View Invoice', 'button_url' => 'invoice_view'],
        ],
        'order_shipment' => [
            ['type' => 'URL', 'text' => 'Track Order', 'button_url' => 'order_view'],
        ],
        'order_cancellation' => [
            ['type' => 'URL', 'text' => 'View Order', 'button_url' => 'order_view'],
        ],
        'customer_registration' => [],
        'order_credit_memo' => [
            ['type' => 'URL', 'text' => 'View Refund', 'button_url' => 'creditmemo_view'],
        ],
        'abandon_cart' => [
            ['type' => 'URL', 'text' => 'Complete Purchase', 'button_url' => 'cart_view'],
        ],
    ];

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Return the order-created template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getOrderTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getValue('event_code', $resolvedStoreId) ?: 'order_created',
            'template_name' => $this->getValue('template_name', $resolvedStoreId),
            'category' => $this->getValue('category', $resolvedStoreId),
            'language' => $this->getValue('language', $resolvedStoreId),
            'header_type' => $this->getValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getValue('header_image', $resolvedStoreId),
            'body_template' => $this->getValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getValue('buttons_json', $resolvedStoreId), 'order_created'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getOrderTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the order template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Resolve the base XML path for a configuration group.
     *
     * @param string $group
     * @return string
     */
    public function getGroupXmlPath(string $group): string
    {
        if ($group === self::GROUP_ABANDONED_CART_TEMPLATE) {
            return self::SECTION_ABANDONED_CART . '/' . $group;
        }

        return self::SECTION . '/' . $group;
    }

    /**
     * Get a single config value from any template group by XML path.
     *
     * @param string $xmlPath
     * @param int|null $storeId
     * @return string
     */
    public function getByXmlPath(string $xmlPath, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            $xmlPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the order_invoice template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getInvoiceTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getInvoiceValue('event_code', $resolvedStoreId) ?: 'order_invoice',
            'template_name' => $this->getInvoiceValue('template_name', $resolvedStoreId),
            'category' => $this->getInvoiceValue('category', $resolvedStoreId),
            'language' => $this->getInvoiceValue('language', $resolvedStoreId),
            'header_type' => $this->getInvoiceValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getInvoiceValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getInvoiceValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getInvoiceValue('header_image', $resolvedStoreId),
            'body_template' => $this->getInvoiceValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getInvoiceValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getInvoiceValue('buttons_json', $resolvedStoreId), 'order_invoice'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured for order_invoice.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasInvoiceBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getInvoiceTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the order_invoice template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getInvoiceValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_INVOICE_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the order_shipment template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getShipmentTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getShipmentValue('event_code', $resolvedStoreId) ?: 'order_shipment',
            'template_name' => $this->getShipmentValue('template_name', $resolvedStoreId),
            'category' => $this->getShipmentValue('category', $resolvedStoreId),
            'language' => $this->getShipmentValue('language', $resolvedStoreId),
            'header_type' => $this->getShipmentValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getShipmentValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getShipmentValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getShipmentValue('header_image', $resolvedStoreId),
            'body_template' => $this->getShipmentValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getShipmentValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getShipmentValue('buttons_json', $resolvedStoreId), 'order_shipment'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured for order_shipment.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasShipmentBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getShipmentTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the order_shipment template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getShipmentValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_SHIPMENT_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the order_cancellation template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getCancellationTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getCancellationValue('event_code', $resolvedStoreId) ?: 'order_cancellation',
            'template_name' => $this->getCancellationValue('template_name', $resolvedStoreId),
            'category' => $this->getCancellationValue('category', $resolvedStoreId),
            'language' => $this->getCancellationValue('language', $resolvedStoreId),
            'header_type' => $this->getCancellationValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getCancellationValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getCancellationValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getCancellationValue('header_image', $resolvedStoreId),
            'body_template' => $this->getCancellationValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getCancellationValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getCancellationValue('buttons_json', $resolvedStoreId), 'order_cancellation'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured for order_cancellation.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasCancellationBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getCancellationTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the order_cancellation template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getCancellationValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_CANCELLATION_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the user_registration template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getRegistrationTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getRegistrationValue('event_code', $resolvedStoreId) ?: 'customer_registration',
            'template_name' => $this->getRegistrationValue('template_name', $resolvedStoreId),
            'category' => $this->getRegistrationValue('category', $resolvedStoreId),
            'language' => $this->getRegistrationValue('language', $resolvedStoreId),
            'header_type' => $this->getRegistrationValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getRegistrationValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getRegistrationValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getRegistrationValue('header_image', $resolvedStoreId),
            'body_template' => $this->getRegistrationValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getRegistrationValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getRegistrationValue('buttons_json', $resolvedStoreId), 'customer_registration'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured for user_registration.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasRegistrationBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getRegistrationTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the user_registration template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getRegistrationValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_USER_REGISTRATION_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the order_credit_memo template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getCreditMemoTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getCreditMemoValue('event_code', $resolvedStoreId) ?: 'order_credit_memo',
            'template_name' => $this->getCreditMemoValue('template_name', $resolvedStoreId),
            'category' => $this->getCreditMemoValue('category', $resolvedStoreId),
            'language' => $this->getCreditMemoValue('language', $resolvedStoreId),
            'header_type' => $this->getCreditMemoValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getCreditMemoValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getCreditMemoValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getCreditMemoValue('header_image', $resolvedStoreId),
            'body_template' => $this->getCreditMemoValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getCreditMemoValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getCreditMemoValue('buttons_json', $resolvedStoreId), 'order_credit_memo'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured for order_credit_memo.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasCreditMemoBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getCreditMemoTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the order_credit_memo template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getCreditMemoValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_CREDIT_MEMO_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the abandoned_cart template configuration for the given store.
     *
     * @param int|null $storeId
     * @return array<string, string>
     */
    public function getAbandonedCartTemplateConfig(?int $storeId = null): array
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $config = [
            'event_code' => $this->getAbandonedCartValue('event_code', $resolvedStoreId) ?: 'abandon_cart',
            'template_name' => $this->getAbandonedCartValue('template_name', $resolvedStoreId),
            'category' => $this->getAbandonedCartValue('category', $resolvedStoreId),
            'language' => $this->getAbandonedCartValue('language', $resolvedStoreId),
            'header_type' => $this->getAbandonedCartValue('header_type', $resolvedStoreId) ?: 'none',
            'header_text' => $this->getAbandonedCartValue('header_text', $resolvedStoreId),
            'header_handle' => $this->getAbandonedCartValue('header_handle', $resolvedStoreId),
            'header_image' => $this->getAbandonedCartValue('header_image', $resolvedStoreId),
            'body_template' => $this->getAbandonedCartValue('body_template', $resolvedStoreId),
            'footer_template' => $this->getAbandonedCartValue('footer_template', $resolvedStoreId),
            'buttons_json' => $this->getButtonsJsonOrDefault($this->getAbandonedCartValue('buttons_json', $resolvedStoreId), 'abandon_cart'),
        ];

        if ($config['language'] === '') {
            $config['language'] = (string)$this->storeManager->getStore($resolvedStoreId)->getLocaleCode();
        }

        return $config;
    }

    /**
     * Check whether a body template is configured for abandoned_cart.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasAbandonedCartBodyTemplate(?int $storeId = null): bool
    {
        return trim($this->getAbandonedCartTemplateConfig($storeId)['body_template']) !== '';
    }

    /**
     * Get a single config value from the abandoned_cart template group.
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
    public function getAbandonedCartValue(string $field, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ABANDONED_CART_TEMPLATE . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if Abandoned Cart WhatsApp is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAbandonedCartEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::SECTION . '/general/enable_abandoned_cart',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get minutes after which a cart is considered abandoned.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAbandonedAfterMinutes(?int $storeId = null): int
    {
        return (int)$this->getAbandonedCartValue('abandon_after_minutes', $storeId) ?: 60;
    }

    /**
     * Get maximum number of quotes to process per cron run.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxQuotesPerRun(?int $storeId = null): int
    {
        return (int)$this->getAbandonedCartValue('max_per_run', $storeId) ?: 50;
    }

    /**
     * Return configured buttons JSON or event-specific defaults when empty.
     *
     * @param string $buttonsJson
     * @param string $eventCode
     * @return string
     */
    private function getButtonsJsonOrDefault(string $buttonsJson, string $eventCode): string
    {
        $buttonsJson = trim($buttonsJson);
        if ($buttonsJson !== '' && $buttonsJson !== '[]') {
            return $buttonsJson;
        }

        return (string)json_encode(self::DEFAULT_BUTTONS[$eventCode] ?? []);
    }
}
