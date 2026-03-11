<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Company\WhatsappTemplate\Api\Data\ComponentVariableInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class ComponentVariable
 */
class ComponentVariable extends AbstractExtensibleModel implements ComponentVariableInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Company\WhatsappTemplate\Model\ResourceModel\ComponentVariable::class);
    }

    /**
     * @inheritdoc
     */
    public function getComponentId()
    {
        return $this->_getData(self::COMPONENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setComponentId($componentId)
    {
        return $this->setData(self::COMPONENT_ID, $componentId);
    }

    /**
     * @inheritdoc
     */
    public function getVariablePosition()
    {
        return $this->_getData(self::VARIABLE_POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setVariablePosition($variablePosition)
    {
        return $this->setData(self::VARIABLE_POSITION, $variablePosition);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->_getData(self::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultValue()
    {
        return $this->_getData(self::DEFAULT_VALUE);
    }

    /**
     * @inheritdoc
     */
    public function setDefaultValue($defaultValue)
    {
        return $this->setData(self::DEFAULT_VALUE, $defaultValue);
    }

    /**
     * @inheritdoc
     */
    public function getParameterFormat()
    {
        return $this->_getData(self::PARAMETER_FORMAT);
    }

    /**
     * @inheritdoc
     */
    public function setParameterFormat($parameterFormat)
    {
        return $this->setData(self::PARAMETER_FORMAT, $parameterFormat);
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
