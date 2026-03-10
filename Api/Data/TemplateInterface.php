<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api\Data;

interface TemplateInterface
{
    const ENTITY_ID = 'entity_id';
    const UUID = 'uuid';
    const TEMPLATE_NAME = 'template_name';
    const TEMPLATE_TYPE = 'template_type';
    const TEMPLATE_CATEGORY = 'template_category';
    const CATEGORY_ID = 'category_id';
    const BUSINESS_ID = 'business_id';
    const LANGUAGE = 'language';
    const STATUS = 'status';
    const META_TEMPLATE_ID = 'meta_template_id';
    const LIBRARY_TEMPLATE_ID = 'library_template_id';
    const COMPONENTS = 'components';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Deprecated fields
    const TEMPLATE_ID = 'template_id';
    const HEADER = 'header';
    const BODY = 'body';
    const FOOTER = 'footer';
    const BUTTON_TYPE = 'button_type';
    const BUTTON_TEXT = 'button_text';
    const BUTTON_URL = 'button_url';
    const BUTTON_PHONE = 'button_phone';

    public function getId();
    public function setId($id);
    public function getUuid(): ?string;
    public function setUuid(string $uuid): self;
    public function getTemplateName(): string;
    public function setTemplateName(string $templateName): self;
    public function getTemplateType(): string;
    public function setTemplateType(string $templateType): self;
    public function getTemplateCategory(): ?string;
    public function setTemplateCategory(?string $templateCategory): self;
    public function getCategoryId(): ?string;
    public function setCategoryId(?string $categoryId): self;
    public function getBusinessId(): ?string;
    public function setBusinessId(?string $businessId): self;
    public function getLanguage(): string;
    public function setLanguage(string $language): self;
    public function getStatus(): string;
    public function setStatus(string $status): self;
    public function getMetaTemplateId(): ?string;
    public function setMetaTemplateId(?string $metaTemplateId): self;
    public function getLibraryTemplateId(): ?string;
    public function setLibraryTemplateId(?string $libraryTemplateId): self;
    public function getComponents(): ?string;
    public function setComponents(?string $components): self;
    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;
    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;

    // Deprecated getters/setters
    public function getTemplateId(): ?string;
    public function setTemplateId(?string $templateId): self;
    public function getHeader(): ?string;
    public function setHeader(?string $header): self;
    public function getBody(): ?string;
    public function setBody(?string $body): self;
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
}
