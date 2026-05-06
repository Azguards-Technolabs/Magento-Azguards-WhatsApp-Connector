<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class WhatsAppTemplateConfig
{
    public const SECTION = 'whatsapp_template';
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
            'buttons_json' => $this->getValue('buttons_json', $resolvedStoreId) ?: '[]',
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
            'buttons_json' => $this->getInvoiceValue('buttons_json', $resolvedStoreId) ?: '[]',
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
            'buttons_json' => $this->getShipmentValue('buttons_json', $resolvedStoreId) ?: '[]',
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
            'buttons_json' => $this->getCancellationValue('buttons_json', $resolvedStoreId) ?: '[]',
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
            'buttons_json' => $this->getCreditMemoValue('buttons_json', $resolvedStoreId) ?: '[]',
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
}
