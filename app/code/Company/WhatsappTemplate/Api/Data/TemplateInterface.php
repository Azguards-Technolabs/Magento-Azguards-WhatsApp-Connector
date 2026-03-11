<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api\Data;

interface TemplateInterface
{
    const ID = 'id';
    const NAME = 'name';
    const LANGUAGE_ID = 'language_id';
    const CATEGORY_ID = 'category_id';
    const TYPE = 'type';
    const STATUS = 'status';

    public function getId();
    public function setId($id);
    public function getName(): ?string;
    public function setName(?string $name): self;
    public function getLanguageId(): ?string;
    public function setLanguageId(?string $languageId): self;
    public function getCategoryId(): ?string;
    public function setCategoryId(?string $categoryId): self;
    public function getType(): ?string;
    public function setType(?string $type): self;
    public function getStatus(): ?string;
    public function setStatus(?string $status): self;
}
