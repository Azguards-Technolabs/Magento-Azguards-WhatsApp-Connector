<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model;

use Magento\Framework\Model\AbstractModel;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;

class Template extends AbstractModel implements TemplateInterface
{
    protected function _construct()
    {
        $this->_init(TemplateResource::class);
    }

    public function getUuid(): ?string
    {
        return $this->getData(self::UUID);
    }

    public function setUuid(string $uuid): TemplateInterface
    {
        return $this->setData(self::UUID, $uuid);
    }

    public function getTemplateName(): string
    {
        return (string)$this->getData(self::TEMPLATE_NAME);
    }

    public function setTemplateName(string $templateName): TemplateInterface
    {
        return $this->setData(self::TEMPLATE_NAME, $templateName);
    }

    public function getTemplateType(): string
    {
        return (string)$this->getData(self::TEMPLATE_TYPE);
    }

    public function setTemplateType(string $templateType): TemplateInterface
    {
        return $this->setData(self::TEMPLATE_TYPE, $templateType);
    }

    public function getTemplateCategory(): ?string
    {
        return $this->getData(self::TEMPLATE_CATEGORY);
    }

    public function setTemplateCategory(?string $templateCategory): TemplateInterface
    {
        return $this->setData(self::TEMPLATE_CATEGORY, $templateCategory);
    }

    public function getCategoryId(): ?string
    {
        return $this->getData(self::CATEGORY_ID);
    }

    public function setCategoryId(?string $categoryId): TemplateInterface
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    public function getBusinessId(): ?string
    {
        return $this->getData(self::BUSINESS_ID);
    }

    public function setBusinessId(?string $businessId): TemplateInterface
    {
        return $this->setData(self::BUSINESS_ID, $businessId);
    }

    public function getLanguage(): string
    {
        return (string)$this->getData(self::LANGUAGE);
    }

    public function setLanguage(string $language): TemplateInterface
    {
        return $this->setData(self::LANGUAGE, $language);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): TemplateInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getMetaTemplateId(): ?string
    {
        return $this->getData(self::META_TEMPLATE_ID);
    }

    public function setMetaTemplateId(?string $metaTemplateId): TemplateInterface
    {
        return $this->setData(self::META_TEMPLATE_ID, $metaTemplateId);
    }

    public function getLibraryTemplateId(): ?string
    {
        return $this->getData(self::LIBRARY_TEMPLATE_ID);
    }

    public function setLibraryTemplateId(?string $libraryTemplateId): TemplateInterface
    {
        return $this->setData(self::LIBRARY_TEMPLATE_ID, $libraryTemplateId);
    }

    public function getComponents(): ?string
    {
        return $this->getData(self::COMPONENTS);
    }

    public function setComponents(?string $components): TemplateInterface
    {
        return $this->setData(self::COMPONENTS, $components);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): TemplateInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): TemplateInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    // Deprecated methods
    public function getTemplateId(): ?string
    {
        return $this->getData(self::TEMPLATE_ID);
    }

    public function setTemplateId(?string $templateId): TemplateInterface
    {
        return $this->setData(self::TEMPLATE_ID, $templateId);
    }

    public function getHeader(): ?string
    {
        return $this->getData(self::HEADER);
    }

    public function setHeader(?string $header): TemplateInterface
    {
        return $this->setData(self::HEADER, $header);
    }

    public function getBody(): ?string
    {
        return $this->getData(self::BODY);
    }

    public function setBody(?string $body): TemplateInterface
    {
        return $this->setData(self::BODY, $body);
    }

    public function getFooter(): ?string
    {
        return $this->getData(self::FOOTER);
    }

    public function setFooter(?string $footer): TemplateInterface
    {
        return $this->setData(self::FOOTER, $footer);
    }

    public function getButtonType(): ?string
    {
        return $this->getData(self::BUTTON_TYPE);
    }

    public function setButtonType(?string $buttonType): TemplateInterface
    {
        return $this->setData(self::BUTTON_TYPE, $buttonType);
    }

    public function getButtonText(): ?string
    {
        return $this->getData(self::BUTTON_TEXT);
    }

    public function setButtonText(?string $buttonText): TemplateInterface
    {
        return $this->setData(self::BUTTON_TEXT, $buttonText);
    }

    public function getButtonUrl(): ?string
    {
        return $this->getData(self::BUTTON_URL);
    }

    public function setButtonUrl(?string $buttonUrl): TemplateInterface
    {
        return $this->setData(self::BUTTON_URL, $buttonUrl);
    }

    public function getButtonPhone(): ?string
    {
        return $this->getData(self::BUTTON_PHONE);
    }

    public function setButtonPhone(?string $buttonPhone): TemplateInterface
    {
        return $this->setData(self::BUTTON_PHONE, $buttonPhone);
    }
}
