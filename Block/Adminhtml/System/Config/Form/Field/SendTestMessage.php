<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SendTestMessage extends Field
{
    /**
     * Render the test button shell.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return '<button id="wa-send-test-message" type="button" class="action-default scalable">'
            . $this->escapeHtml(__('Send Test Message'))
            . '</button><div id="wa-send-test-status" class="wa-test-message-status"></div>';
    }
}
