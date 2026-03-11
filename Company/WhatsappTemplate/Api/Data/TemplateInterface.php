<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface TemplateInterface
 */
interface TemplateInterface extends ExtensibleDataInterface
{
    const ID = 'id';
    const NAME = 'name';
    const LANGUAGE_ID = 'language_id';
    const CATEGORY_ID = 'category_id';
    const META_TEMPLATE_ID = 'meta_template_id';
    const LIBRARY_TEMPLATE_ID = 'library_template_id';
    const USER_ID = 'user_id';
    const BUSINESS_ID = 'business_id';
    const TYPE = 'type';
    const STATUS = 'status';
    const HEALTH = 'health';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
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
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getLanguageId();

    /**
     * @param string $languageId
     * @return $this
     */
    public function setLanguageId($languageId);

    /**
     * @return string|null
     */
    public function getCategoryId();

    /**
     * @param string $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId);

    /**
     * @return string|null
     */
    public function getMetaTemplateId();

    /**
     * @param string $metaTemplateId
     * @return $this
     */
    public function setMetaTemplateId($metaTemplateId);

    /**
     * @return string|null
     */
    public function getLibraryTemplateId();

    /**
     * @param string $libraryTemplateId
     * @return $this
     */
    public function setLibraryTemplateId($libraryTemplateId);

    /**
     * @return int|null
     */
    public function getUserId();

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId);

    /**
     * @return int|null
     */
    public function getBusinessId();

    /**
     * @param int $businessId
     * @return $this
     */
    public function setBusinessId($businessId);

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
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string|null
     */
    public function getHealth();

    /**
     * @param string $health
     * @return $this
     */
    public function setHealth($health);

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
    public function getDeletedAt();

    /**
     * @param string $deletedAt
     * @return $this
     */
    public function setDeletedAt($deletedAt);

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
