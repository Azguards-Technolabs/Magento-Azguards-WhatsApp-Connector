<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface ComponentVariableInterface
 */
interface ComponentVariableInterface extends ExtensibleDataInterface
{
    const ID = 'id';
    const COMPONENT_ID = 'component_id';
    const VARIABLE_POSITION = 'variable_position';
    const TYPE = 'type';
    const DEFAULT_VALUE = 'default_value';
    const PARAMETER_FORMAT = 'parameter_format';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getComponentId();

    /**
     * @param int $componentId
     * @return $this
     */
    public function setComponentId($componentId);

    /**
     * @return int|null
     */
    public function getVariablePosition();

    /**
     * @param int $variablePosition
     * @return $this
     */
    public function setVariablePosition($variablePosition);

    /**
     * @return string|null
     */
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * @return string|null
     */
    public function getDefaultValue();

    /**
     * @param string $defaultValue
     * @return $this
     */
    public function setDefaultValue($defaultValue);

    /**
     * @return string|null
     */
    public function getParameterFormat();

    /**
     * @param string $parameterFormat
     * @return $this
     */
    public function setParameterFormat($parameterFormat);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string|null
     */
    public function getCreatedBy();

    /**
     * @param string $createdBy
     * @return $this
     */
    public function setCreatedBy($createdBy);

    /**
     * @return string|null
     */
    public function getUpdatedBy();

    /**
     * @param string $updatedBy
     * @return $this
     */
    public function setUpdatedBy($updatedBy);
}
