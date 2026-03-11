<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Magento\Framework\Model\AbstractModel;
use Company\WhatsappTemplate\Api\Data\TemplateInterface;

class Template extends AbstractModel implements TemplateInterface
{
    protected function _construct()
    {
        $this->_init(\Company\WhatsappTemplate\Model\ResourceModel\Template::class);
    }

    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    public function setName(?string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getLanguageId(): ?string
    {
        return $this->getData(self::LANGUAGE_ID);
    }

    public function setLanguageId(?string $languageId): self
    {
        return $this->setData(self::LANGUAGE_ID, $languageId);
    }

    public function getCategoryId(): ?string
    {
        return $this->getData(self::CATEGORY_ID);
    }

    public function setCategoryId(?string $categoryId): self
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    public function getType(): ?string
    {
        return $this->getData(self::TYPE);
    }

    public function setType(?string $type): self
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(?string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }
}
