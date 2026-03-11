<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Company\WhatsappTemplate\Api\Data\TemplateButtonInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class TemplateButton
 */
class TemplateButton extends AbstractExtensibleModel implements TemplateButtonInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Company\WhatsappTemplate\Model\ResourceModel\TemplateButton::class);
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
    public function isCarouselButton()
    {
        return (bool)$this->_getData(self::IS_CAROUSEL_BUTTON);
    }

    /**
     * @inheritdoc
     */
    public function setIsCarouselButton($isCarouselButton)
    {
        return $this->setData(self::IS_CAROUSEL_BUTTON, $isCarouselButton);
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
    public function getText()
    {
        return $this->_getData(self::TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setText($text)
    {
        return $this->setData(self::TEXT, $text);
    }

    /**
     * @inheritdoc
     */
    public function getPhoneNumber()
    {
        return $this->_getData(self::PHONE_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setPhoneNumber($phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->_getData(self::URL);
    }

    /**
     * @inheritdoc
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
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
