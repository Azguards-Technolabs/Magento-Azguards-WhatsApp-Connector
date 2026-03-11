<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface TemplateComponentInterface
 */
interface TemplateComponentInterface extends ExtensibleDataInterface
{
    const ID = 'id';
    const TEMPLATE_ID = 'template_id';
    const IS_CAROUSEL_COMPONENT = 'is_carousel_component';
    const CARD_ORDER = 'card_order';
    const COMPONENT_TYPE = 'component_type';
    const COMPONENT_FORMAT = 'component_format';
    const COMPONENT_DATA = 'component_data';
    const ORDER = 'order';
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
    public function getTemplateId();

    /**
     * @param int $templateId
     * @return $this
     */
    public function setTemplateId($templateId);

    /**
     * @return bool
     */
    public function isCarouselComponent();

    /**
     * @param bool $isCarouselComponent
     * @return $this
     */
    public function setIsCarouselComponent($isCarouselComponent);

    /**
     * @return int|null
     */
    public function getCardOrder();

    /**
     * @param int $cardOrder
     * @return $this
     */
    public function setCardOrder($cardOrder);

    /**
     * @return string|null
     */
    public function getComponentType();

    /**
     * @param string $componentType
     * @return $this
     */
    public function setComponentType($componentType);

    /**
     * @return string|null
     */
    public function getComponentFormat();

    /**
     * @param string $componentFormat
     * @return $this
     */
    public function setComponentFormat($componentFormat);

    /**
     * @return string|null
     */
    public function getComponentData();

    /**
     * @param string $componentData
     * @return $this
     */
    public function setComponentData($componentData);

    /**
     * @return int|null
     */
    public function getOrder();

    /**
     * @param int $order
     * @return $this
     */
    public function setOrder($order);

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
