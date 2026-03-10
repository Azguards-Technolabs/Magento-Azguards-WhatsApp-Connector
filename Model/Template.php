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

    public function getTemplateId(): ?string
    {
        return $this->getData(self::TEMPLATE_ID);
    }

    public function setTemplateId(?string $templateId): self
    {
        return $this->setData(self::TEMPLATE_ID, $templateId);
    }

    public function getTemplateName(): string
    {
        return (string)$this->getData(self::TEMPLATE_NAME);
    }

    public function setTemplateName(string $templateName): self
    {
        return $this->setData(self::TEMPLATE_NAME, $templateName);
    }

    public function getTemplateType(): string
    {
        return (string)$this->getData(self::TEMPLATE_TYPE);
    }

    public function setTemplateType(string $templateType): self
    {
        return $this->setData(self::TEMPLATE_TYPE, $templateType);
    }

    public function getTemplateCategory(): string
    {
        return (string)$this->getData(self::TEMPLATE_CATEGORY);
    }

    public function setTemplateCategory(string $templateCategory): self
    {
        return $this->setData(self::TEMPLATE_CATEGORY, $templateCategory);
    }

    public function getLanguage(): string
    {
        return (string)$this->getData(self::LANGUAGE);
    }

    public function setLanguage(string $language): self
    {
        return $this->setData(self::LANGUAGE, $language);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getHeader(): ?string
    {
        return $this->getData(self::HEADER);
    }

    public function setHeader(?string $header): self
    {
        return $this->setData(self::HEADER, $header);
    }

    public function getBody(): string
    {
        return (string)$this->getData(self::BODY);
    }

    public function setBody(string $body): self
    {
        return $this->setData(self::BODY, $body);
    }

    public function getFooter(): ?string
    {
        return $this->getData(self::FOOTER);
    }

    public function setFooter(?string $footer): self
    {
        return $this->setData(self::FOOTER, $footer);
    }

    public function getButtonType(): ?string
    {
        return $this->getData(self::BUTTON_TYPE);
    }

    public function setButtonType(?string $buttonType): self
    {
        return $this->setData(self::BUTTON_TYPE, $buttonType);
    }

    public function getButtonText(): ?string
    {
        return $this->getData(self::BUTTON_TEXT);
    }

    public function setButtonText(?string $buttonText): self
    {
        return $this->setData(self::BUTTON_TEXT, $buttonText);
    }

    public function getButtonUrl(): ?string
    {
        return $this->getData(self::BUTTON_URL);
    }

    public function setButtonUrl(?string $buttonUrl): self
    {
        return $this->setData(self::BUTTON_URL, $buttonUrl);
    }

    public function getButtonPhone(): ?string
    {
        return $this->getData(self::BUTTON_PHONE);
    }

    public function setButtonPhone(?string $buttonPhone): self
    {
        return $this->setData(self::BUTTON_PHONE, $buttonPhone);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
