<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model;

use Magento\Framework\Model\AbstractModel;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;

class Template extends AbstractModel implements TemplateInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(TemplateResource::class);
    }

    /**
     * Get template ID
     *
     * @return string|null
     */
    public function getTemplateId(): ?string
    {
        return $this->getData(self::TEMPLATE_ID);
    }

    /**
     * Set template ID
     *
     * @param string|null $templateId
     * @return self
     */
    public function setTemplateId(?string $templateId): self
    {
        return $this->setData(self::TEMPLATE_ID, $templateId);
    }

    /**
     * Get template name
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return (string)$this->getData(self::TEMPLATE_NAME);
    }

    /**
     * Set template name
     *
     * @param string $templateName
     * @return self
     */
    public function setTemplateName(string $templateName): self
    {
        return $this->setData(self::TEMPLATE_NAME, $templateName);
    }

    /**
     * Get template type
     *
     * @return string
     */
    public function getTemplateType(): string
    {
        return (string)$this->getData(self::TEMPLATE_TYPE);
    }

    /**
     * Set template type
     *
     * @param string $templateType
     * @return self
     */
    public function setTemplateType(string $templateType): self
    {
        return $this->setData(self::TEMPLATE_TYPE, $templateType);
    }

    /**
     * Get template category
     *
     * @return string
     */
    public function getTemplateCategory(): string
    {
        return (string)$this->getData(self::TEMPLATE_CATEGORY);
    }

    /**
     * Set template category
     *
     * @param string $templateCategory
     * @return self
     */
    public function setTemplateCategory(string $templateCategory): self
    {
        return $this->setData(self::TEMPLATE_CATEGORY, $templateCategory);
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return (string)$this->getData(self::LANGUAGE);
    }

    /**
     * Set language
     *
     * @param string $language
     * @return self
     */
    public function setLanguage(string $language): self
    {
        return $this->setData(self::LANGUAGE, $language);
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return self
     */
    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get header
     *
     * @return string|null
     */
    public function getHeader(): ?string
    {
        return $this->getData(self::HEADER);
    }

    /**
     * Set header
     *
     * @param string|null $header
     * @return self
     */
    public function setHeader(?string $header): self
    {
        return $this->setData(self::HEADER, $header);
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getHeaderImage(): ?string
    {
        return $this->getData(self::HEADER_IMAGE);
    }

    public function setHeaderImage(?string $headerImage): self
    {
        return $this->setData(self::HEADER_IMAGE, $headerImage);
    }

    public function getBody(): string
    {
        return (string)$this->getData(self::BODY);
    }

    /**
     * Set body
     *
     * @param string $body
     * @return self
     */
    public function setBody(string $body): self
    {
        return $this->setData(self::BODY, $body);
    }

    /**
     * Get footer
     *
     * @return string|null
     */
    public function getFooter(): ?string
    {
        return $this->getData(self::FOOTER);
    }

    /**
     * Set footer
     *
     * @param string|null $footer
     * @return self
     */
    public function setFooter(?string $footer): self
    {
        return $this->setData(self::FOOTER, $footer);
    }

    /**
     * Get button type
     *
     * @return string|null
     */
    public function getButtonType(): ?string
    {
        return $this->getData(self::BUTTON_TYPE);
    }

    /**
     * Set button type
     *
     * @param string|null $buttonType
     * @return self
     */
    public function setButtonType(?string $buttonType): self
    {
        return $this->setData(self::BUTTON_TYPE, $buttonType);
    }

    /**
     * Get button text
     *
     * @return string|null
     */
    public function getButtonText(): ?string
    {
        return $this->getData(self::BUTTON_TEXT);
    }

    /**
     * Set button text
     *
     * @param string|null $buttonText
     * @return self
     */
    public function setButtonText(?string $buttonText): self
    {
        return $this->setData(self::BUTTON_TEXT, $buttonText);
    }

    /**
     * Get button URL
     *
     * @return string|null
     */
    public function getButtonUrl(): ?string
    {
        return $this->getData(self::BUTTON_URL);
    }

    /**
     * Set button URL
     *
     * @param string|null $buttonUrl
     * @return self
     */
    public function setButtonUrl(?string $buttonUrl): self
    {
        return $this->setData(self::BUTTON_URL, $buttonUrl);
    }

    /**
     * Get button phone
     *
     * @return string|null
     */
    public function getButtonPhone(): ?string
    {
        return $this->getData(self::BUTTON_PHONE);
    }

    /**
     * Set button phone
     *
     * @param string|null $buttonPhone
     * @return self
     */
    public function setButtonPhone(?string $buttonPhone): self
    {
        return $this->setData(self::BUTTON_PHONE, $buttonPhone);
    }

    /**
     * Get variables
     *
     * @return string|null
     */

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get Buttons (JSON)
     *
     * @return string|null
     */
    public function getButtons(): ?string
    {
        return $this->getData(self::BUTTONS);
    }

    /**
     * Set Buttons (JSON)
     *
     * @param string|null $buttons
     * @return self
     */
    public function setButtons(?string $buttons): self
    {
        return $this->setData(self::BUTTONS, $buttons);
    }
}
