<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api\Data;

interface TemplateInterface
{
    const ENTITY_ID = 'entity_id';
    const TEMPLATE_ID = 'template_id';
    const TEMPLATE_NAME = 'template_name';
    const TEMPLATE_TYPE = 'template_type';
    const TEMPLATE_CATEGORY = 'template_category';
    const LANGUAGE = 'language';
    const STATUS = 'status';
    const HEADER = 'header';
    const HEADER_IMAGE = 'header_image';
    const BODY = 'body';
    const FOOTER = 'footer';
    const BUTTON_TYPE = 'button_type';
    const BUTTON_TEXT = 'button_text';
    const BUTTON_URL = 'button_url';
    const BUTTON_PHONE = 'button_phone';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const BUTTONS = 'buttons';

    /**
     * Get Entity ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set Entity ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get ERP Template ID
     *
     * @return string|null
     */
    public function getTemplateId(): ?string;

    /**
     * Set ERP Template ID
     *
     * @param string|null $templateId
     * @return $this
     */
    public function setTemplateId(?string $templateId): self;

    /**
     * Get Template Name
     *
     * @return string
     */
    public function getTemplateName(): string;

    /**
     * Set Template Name
     *
     * @param string $templateName
     * @return $this
     */
    public function setTemplateName(string $templateName): self;

    /**
     * Get Template Type
     *
     * @return string
     */
    public function getTemplateType(): string;

    /**
     * Set Template Type
     *
     * @param string $templateType
     * @return $this
     */
    public function setTemplateType(string $templateType): self;

    /**
     * Get Template Category
     *
     * @return string
     */
    public function getTemplateCategory(): string;

    /**
     * Set Template Category
     *
     * @param string $templateCategory
     * @return $this
     */
    public function setTemplateCategory(string $templateCategory): self;

    /**
     * Get Language
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Set Language
     *
     * @param string $language
     * @return $this
     */
    public function setLanguage(string $language): self;

    /**
     * Get Status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get Header Content
     *
     * @return string|null
     */
    public function getHeader(): ?string;

    /**
     * Set Header Content
     *
     * @param string|null $header
     * @return $this
     */
    public function setHeader(?string $header): self;

    /**
     * Get Header Image
     *
     * @return string|null
     */
    public function getHeaderImage(): ?string;

    /**
     * Set Header Image
     *
     * @param string|null $headerImage
     * @return $this
     */
    public function setHeaderImage(?string $headerImage): self;

    /**
     * Get Body Content
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * Set Body Content
     *
     * @param string $body
     * @return $this
     */
    public function setBody(string $body): self;

    /**
     * Get Footer Content
     *
     * @return string|null
     */
    public function getFooter(): ?string;

    /**
     * Set Footer Content
     *
     * @param string|null $footer
     * @return $this
     */
    public function setFooter(?string $footer): self;

    /**
     * Get Button Type
     *
     * @return string|null
     */
    public function getButtonType(): ?string;

    /**
     * Set Button Type
     *
     * @param string|null $buttonType
     * @return $this
     */
    public function setButtonType(?string $buttonType): self;

    /**
     * Get Button Text
     *
     * @return string|null
     */
    public function getButtonText(): ?string;

    /**
     * Set Button Text
     *
     * @param string|null $buttonText
     * @return $this
     */
    public function setButtonText(?string $buttonText): self;

    /**
     * Get Button URL
     *
     * @return string|null
     */
    public function getButtonUrl(): ?string;

    /**
     * Set Button URL
     *
     * @param string|null $buttonUrl
     * @return $this
     */
    public function setButtonUrl(?string $buttonUrl): self;

    /**
     * Get Button Phone
     *
     * @return string|null
     */
    public function getButtonPhone(): ?string;

    /**
     * Set Button Phone
     *
     * @param string|null $buttonPhone
     * @return $this
     */
    public function setButtonPhone(?string $buttonPhone): self;


    /**
     * Get Creation Time
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Creation Time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get Update Time
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set Update Time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Get Buttons (JSON)
     *
     * @return string|null
     */
    public function getButtons(): ?string;

    /**
     * Set Buttons (JSON)
     *
     * @param string|null $buttons
     * @return $this
     */
    public function setButtons(?string $buttons): self;
}
