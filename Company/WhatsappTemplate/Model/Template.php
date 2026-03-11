<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Company\WhatsappTemplate\Api\Data\TemplateInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class Template
 */
class Template extends AbstractExtensibleModel implements TemplateInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Company\WhatsappTemplate\Model\ResourceModel\Template::class);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->_getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getLanguageId()
    {
        return $this->_getData(self::LANGUAGE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLanguageId($languageId)
    {
        return $this->setData(self::LANGUAGE_ID, $languageId);
    }

    /**
     * @inheritdoc
     */
    public function getCategoryId()
    {
        return $this->_getData(self::CATEGORY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    /**
     * @inheritdoc
     */
    public function getMetaTemplateId()
    {
        return $this->_getData(self::META_TEMPLATE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMetaTemplateId($metaTemplateId)
    {
        return $this->setData(self::META_TEMPLATE_ID, $metaTemplateId);
    }

    /**
     * @inheritdoc
     */
    public function getLibraryTemplateId()
    {
        return $this->_getData(self::LIBRARY_TEMPLATE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLibraryTemplateId($libraryTemplateId)
    {
        return $this->setData(self::LIBRARY_TEMPLATE_ID, $libraryTemplateId);
    }

    /**
     * @inheritdoc
     */
    public function getUserId()
    {
        return $this->_getData(self::USER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setUserId($userId)
    {
        return $this->setData(self::USER_ID, $userId);
    }

    /**
     * @inheritdoc
     */
    public function getBusinessId()
    {
        return $this->_getData(self::BUSINESS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setBusinessId($businessId)
    {
        return $this->setData(self::BUSINESS_ID, $businessId);
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
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getHealth()
    {
        return $this->_getData(self::HEALTH);
    }

    /**
     * @inheritdoc
     */
    public function setHealth($health)
    {
        return $this->setData(self::HEALTH, $health);
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
    public function getDeletedAt()
    {
        return $this->_getData(self::DELETED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setDeletedAt($deletedAt)
    {
        return $this->setData(self::DELETED_AT, $deletedAt);
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
