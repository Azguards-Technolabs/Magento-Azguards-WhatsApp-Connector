<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Backend model for buttons_json config fields.
 *
 * Resolves the {{var store.base_url}} placeholder in button URLs to the
 * actual Magento store base URL at config-save time, so the stored value
 * is always a fully-qualified URL rather than a template expression.
 */
class ButtonsJson extends Value
{
    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Json $json,
        StoreManagerInterface $storeManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->json = $json;
        $this->storeManager = $storeManager;
    }

    /**
     * Replace {{var store.base_url}} in button URLs with the actual store base URL before saving.
     *
     * @return $this
     */
    public function beforeSave(): self
    {
        $value = (string) $this->getValue();

        if ($value === '' || strpos($value, 'store.base_url') === false) {
            return parent::beforeSave();
        }

        try {
            $buttons = $this->json->unserialize($value);

            if (!is_array($buttons)) {
                return parent::beforeSave();
            }

            $baseUrl = $this->storeManager->getStore()->getBaseUrl();

            foreach ($buttons as &$button) {
                if (!is_array($button)) {
                    continue;
                }

                // Resolve in both common key names used across the codebase
                foreach (['url', 'button_url', 'value'] as $urlKey) {
                    if (isset($button[$urlKey]) && is_string($button[$urlKey])) {
                        $button[$urlKey] = preg_replace(
                            '/\{\{\s*var\s+store\.base_url\s*\}\}/',
                            $baseUrl,
                            $button[$urlKey]
                        );
                    }
                }
            }
            unset($button);

            $this->setValue($this->json->serialize($buttons));
        } catch (\Exception $e) {
            $this->logger->error('Error serializing buttons JSON: ' . $e->getMessage());
            // If JSON is invalid, leave value as-is and let it be saved raw
        }

        return parent::beforeSave();
    }
}
