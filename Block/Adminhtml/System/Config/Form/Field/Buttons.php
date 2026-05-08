<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config\Form\Field;

use Azguards\WhatsAppConnect\Model\Source\ButtonType;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Field renderer for WhatsApp template buttons.
 */
class Buttons extends Field
{
    /**
     * @var ButtonType
     */
    private ButtonType $buttonType;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ButtonType $buttonType
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        ButtonType $buttonType,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->buttonType = $buttonType;
        $this->json = $json;
        $this->setTemplate('Azguards_WhatsAppConnect::system/config/field/buttons.phtml');
    }

    /**
     * Render the buttons field.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->setData('element', $element);

        return $this->_toHtml();
    }

    /**
     * Get button type options.
     *
     * @return array<int, array<string, string>>
     */
    public function getButtonTypeOptions(): array
    {
        return $this->buttonType->toOptionArray();
    }

    /**
     * Get buttons configuration in JSON format.
     *
     * @return string
     */
    public function getButtonsJson(): string
    {
        return $this->json->serialize($this->getButtonTypeOptions());
    }
}
