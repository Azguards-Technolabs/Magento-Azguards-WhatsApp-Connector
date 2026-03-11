<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface TemplateButtonInterface
 */
interface TemplateButtonInterface extends ExtensibleDataInterface
{
    const ID = 'id';
    const TEMPLATE_ID = 'template_id';
    const IS_CAROUSEL_BUTTON = 'is_carousel_button';
    const CARD_ORDER = 'card_order';
    const TYPE = 'type';
    const ORDER = 'order';
    const TEXT = 'text';
    const PHONE_NUMBER = 'phone_number';
    const URL = 'url';
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
    public function isCarouselButton();

    /**
     * @param bool $isCarouselButton
     * @return $this
     */
    public function setIsCarouselButton($isCarouselButton);

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
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);

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
    public function getText();

    /**
     * @param string $text
     * @return $this
     */
    public function setText($text);

    /**
     * @return string|null
     */
    public function getPhoneNumber();

    /**
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * @return string|null
     */
    public function getUrl();

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url);

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
