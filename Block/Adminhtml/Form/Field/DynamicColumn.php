<?php
  
namespace Azguards\WhatsAppConnect\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class DynamicColumn extends Select
{
    /**
     * SetInputName function
     *
     * @param [type] $value
     * @return void
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * SetInputId function
     *
     * @param [type] $value
     * @return void
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * GetSourceOptions function
     *
     * @return array
     */
    private function getSourceOptions()
    {
        return [
            ['label' => 'Order Id', 'value' => 'order_id'],
            ['label' => 'Order Total', 'value' => 'order_total'],
            ['label' => 'Purchased Product', 'value' => 'product_name'],
            ['label' => 'Customer Name', 'value' => 'customer_name'],
        ];
    }
}