<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Field renderer for WhatsApp template language.
 */
class Language extends Field
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param StoreManagerInterface $storeManager
     * @param array<string, mixed> $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * Render a read-only locale field sourced from the active store view.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $localeCode = $storeId
            ? (string)$this->storeManager->getStore($storeId)->getLocaleCode()
            : (string)($element->getEscapedValue() ?: $this->storeManager->getStore()->getLocaleCode());

        return sprintf(
            '<div class="wa-template-language"><input type="text" readonly="readonly" ' .
            'class="input-text admin__control-text" id="%s" name="%s" value="%s"/></div>',
            $element->getHtmlId(),
            $element->getName(),
            $this->escapeHtmlAttr($localeCode)
        );
    }
}
