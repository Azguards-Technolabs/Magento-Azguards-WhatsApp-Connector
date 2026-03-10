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
    const BODY = 'body';
    const FOOTER = 'footer';
    const BUTTON_TYPE = 'button_type';
    const BUTTON_TEXT = 'button_text';
    const BUTTON_URL = 'button_url';
    const BUTTON_PHONE = 'button_phone';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getId();
    public function setId($id);
    public function getTemplateId(): ?string;
    public function setTemplateId(?string $templateId): self;
    public function getTemplateName(): string;
    public function setTemplateName(string $templateName): self;
    public function getTemplateType(): string;
    public function setTemplateType(string $templateType): self;
    public function getTemplateCategory(): string;
    public function setTemplateCategory(string $templateCategory): self;
    public function getLanguage(): string;
    public function setLanguage(string $language): self;
    public function getStatus(): string;
    public function setStatus(string $status): self;
    public function getHeader(): ?string;
    public function setHeader(?string $header): self;
    public function getBody(): string;
    public function setBody(string $body): self;
    public function getFooter(): ?string;
    public function setFooter(?string $footer): self;
    public function getButtonType(): ?string;
    public function setButtonType(?string $buttonType): self;
    public function getButtonText(): ?string;
    public function setButtonText(?string $buttonText): self;
    public function getButtonUrl(): ?string;
    public function setButtonUrl(?string $buttonUrl): self;
    public function getButtonPhone(): ?string;
    public function setButtonPhone(?string $buttonPhone): self;
    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;
    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;
}
