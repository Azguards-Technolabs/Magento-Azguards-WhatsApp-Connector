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
}
