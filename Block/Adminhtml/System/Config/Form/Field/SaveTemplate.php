<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SaveTemplate extends Field
{
    /**
     * Render the save template button shell.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return '<button id="wa-save-template" type="button" class="action-default scalable">'
            . $this->escapeHtml(__('Save Template'))
            . '</button><div id="wa-save-template-status" class="wa-test-message-status"></div>';
    }
}
