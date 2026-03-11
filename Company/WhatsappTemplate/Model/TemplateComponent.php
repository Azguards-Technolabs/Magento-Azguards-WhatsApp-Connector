<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Company\WhatsappTemplate\Api\Data\TemplateComponentInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class TemplateComponent
 */
class TemplateComponent extends AbstractExtensibleModel implements TemplateComponentInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Company\WhatsappTemplate\Model\ResourceModel\TemplateComponent::class);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateId()
    {
        return $this->_getData(self::TEMPLATE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTemplateId($templateId)
    {
        return $this->setData(self::TEMPLATE_ID, $templateId);
    }

    /**
     * @inheritdoc
     */
    public function isCarouselComponent()
    {
        return (bool)$this->_getData(self::IS_CAROUSEL_COMPONENT);
    }

    /**
     * @inheritdoc
     */
    public function setIsCarouselComponent($isCarouselComponent)
    {
        return $this->setData(self::IS_CAROUSEL_COMPONENT, $isCarouselComponent);
    }

    /**
     * @inheritdoc
     */
    public function getCardOrder()
    {
        return $this->_getData(self::CARD_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setCardOrder($cardOrder)
    {
        return $this->setData(self::CARD_ORDER, $cardOrder);
    }

    /**
     * @inheritdoc
     */
    public function getComponentType()
    {
        return $this->_getData(self::COMPONENT_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setComponentType($componentType)
    {
        return $this->setData(self::COMPONENT_TYPE, $componentType);
    }

    /**
     * @inheritdoc
     */
    public function getComponentFormat()
    {
        return $this->_getData(self::COMPONENT_FORMAT);
    }

    /**
     * @inheritdoc
     */
    public function setComponentFormat($componentFormat)
    {
        return $this->setData(self::COMPONENT_FORMAT, $componentFormat);
    }

    /**
     * @inheritdoc
     */
    public function getComponentData()
    {
        return $this->_getData(self::COMPONENT_DATA);
    }

    /**
     * @inheritdoc
     */
    public function setComponentData($componentData)
    {
        return $this->setData(self::COMPONENT_DATA, $componentData);
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return $this->_getData(self::ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setOrder($order)
    {
        return $this->setData(self::ORDER, $order);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedBy()
    {
        return $this->_getData(self::CREATED_BY);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedBy($createdBy)
    {
        return $this->setData(self::CREATED_BY, $createdBy);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedBy()
    {
        return $this->_getData(self::UPDATED_BY);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedBy($updatedBy)
    {
        return $this->setData(self::UPDATED_BY, $updatedBy);
    }
}
