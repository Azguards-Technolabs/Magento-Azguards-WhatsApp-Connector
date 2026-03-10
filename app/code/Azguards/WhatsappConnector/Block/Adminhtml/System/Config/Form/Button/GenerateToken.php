<?php
namespace Azguards\WhatsappConnector\Block\Adminhtml\System\Config\Form\Button;

use Magento\Config\Block\System\Config\Form\Field;

class GenerateToken extends Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return '<button type="button" class="action-default" id="generate_token"><span>Generate Token</span></button>';
    }
}
