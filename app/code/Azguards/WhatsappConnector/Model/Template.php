<?php
namespace Azguards\WhatsappConnector\Model;

use Azguards\WhatsappConnector\Api\Data\TemplateInterface;
use Magento\Framework\Model\AbstractModel;

class Template extends AbstractModel implements TemplateInterface
{
    protected function _construct()
    {
        $this->_init(\Azguards\WhatsappConnector\Model\ResourceModel\Template::class);
    }

    public function getTemplateName()
    {
        return $this->getData(self::TEMPLATE_NAME);
    }

    public function setTemplateName($templateName)
    {
        return $this->setData(self::TEMPLATE_NAME, $templateName);
    }

    public function getTemplateType()
    {
        return $this->getData(self::TEMPLATE_TYPE);
    }

    public function setTemplateType($templateType)
    {
        return $this->setData(self::TEMPLATE_TYPE, $templateType);
    }

    public function getTemplateCategory()
    {
        return $this->getData(self::TEMPLATE_CATEGORY);
    }

    public function setTemplateCategory($templateCategory)
    {
        return $this->setData(self::TEMPLATE_CATEGORY, $templateCategory);
    }

    public function getLanguage()
    {
        return $this->getData(self::LANGUAGE);
    }

    public function setLanguage($language)
    {
        return $this->setData(self::LANGUAGE, $language);
    }

    public function getHeaderText()
    {
        return $this->getData(self::HEADER_TEXT);
    }

    public function setHeaderText($headerText)
    {
        return $this->setData(self::HEADER_TEXT, $headerText);
    }

    public function getBodyText()
    {
        return $this->getData(self::BODY_TEXT);
    }

    public function setBodyText($bodyText)
    {
        return $this->setData(self::BODY_TEXT, $bodyText);
    }

    public function getFooterText()
    {
        return $this->getData(self::FOOTER_TEXT);
    }

    public function setFooterText($footerText)
    {
        return $this->setData(self::FOOTER_TEXT, $footerText);
    }

    public function getButtonType()
    {
        return $this->getData(self::BUTTON_TYPE);
    }

    public function setButtonType($buttonType)
    {
        return $this->setData(self::BUTTON_TYPE, $buttonType);
    }

    public function getButtonText()
    {
        return $this->getData(self::BUTTON_TEXT);
    }

    public function setButtonText($buttonText)
    {
        return $this->setData(self::BUTTON_TEXT, $buttonText);
    }

    public function getButtonUrl()
    {
        return $this->getData(self::BUTTON_URL);
    }

    public function setButtonUrl($buttonUrl)
    {
        return $this->setData(self::BUTTON_URL, $buttonUrl);
    }

    public function getButtonPhone()
    {
        return $this->getData(self::BUTTON_PHONE);
    }

    public function setButtonPhone($buttonPhone)
    {
        return $this->setData(self::BUTTON_PHONE, $buttonPhone);
    }

    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
