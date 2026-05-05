<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class HeaderMedia extends Field
{
    /**
     * @param Context $context
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('Azguards_WhatsAppConnect::system/config/field/header-media.phtml');
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->setData('element', $element);

        return $this->_toHtml();
    }
}
