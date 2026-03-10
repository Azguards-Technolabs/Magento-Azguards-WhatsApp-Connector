<?php
namespace Azguards\WhatsappConnector\Api\Data;

interface TemplateInterface
{
    public const TEMPLATE_ID = 'template_id';
    public const TEMPLATE_NAME = 'template_name';
    public const TEMPLATE_TYPE = 'template_type';
    public const TEMPLATE_CATEGORY = 'template_category';
    public const LANGUAGE = 'language';
    public const HEADER_TEXT = 'header_text';
    public const BODY_TEXT = 'body_text';
    public const FOOTER_TEXT = 'footer_text';
    public const BUTTON_TYPE = 'button_type';
    public const BUTTON_TEXT = 'button_text';
    public const BUTTON_URL = 'button_url';
    public const BUTTON_PHONE = 'button_phone';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getId();
    public function setId($id);

    public function getTemplateName();
    public function setTemplateName($templateName);

    public function getTemplateType();
    public function setTemplateType($templateType);

    public function getTemplateCategory();
    public function setTemplateCategory($templateCategory);

    public function getLanguage();
    public function setLanguage($language);

    public function getHeaderText();
    public function setHeaderText($headerText);

    public function getBodyText();
    public function setBodyText($bodyText);

    public function getFooterText();
    public function setFooterText($footerText);

    public function getButtonType();
    public function setButtonType($buttonType);

    public function getButtonText();
    public function setButtonText($buttonText);

    public function getButtonUrl();
    public function setButtonUrl($buttonUrl);

    public function getButtonPhone();
    public function setButtonPhone($buttonPhone);

    public function getStatus();
    public function setStatus($status);

    public function getCreatedAt();
    public function setCreatedAt($createdAt);

    public function getUpdatedAt();
    public function setUpdatedAt($updatedAt);
}
