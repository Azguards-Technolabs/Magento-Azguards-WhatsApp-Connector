<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Azguards\WhatsAppConnect\Model\Api\TemplateApi;

class MetaWhatsAppApiClient
{
    /**
     * @var TemplateApi
     */
    private TemplateApi $templateApi;

    /**
     * @var MetaTemplatePayloadBuilder
     */
    private MetaTemplatePayloadBuilder $payloadBuilder;

    /**
     * @param TemplateApi $templateApi
     * @param MetaTemplatePayloadBuilder $payloadBuilder
     */
    public function __construct(
        TemplateApi $templateApi,
        MetaTemplatePayloadBuilder $payloadBuilder
    ) {
        $this->templateApi = $templateApi;
        $this->payloadBuilder = $payloadBuilder;
    }

    /**
     * Build the payload and create template
     *
     * @param TemplateInterface $template
     * @return array
     * @throws \Exception
     */
    public function createTemplate(TemplateInterface $template): array
    {
        $payload = $this->payloadBuilder->build($template);
        return $this->templateApi->createTemplate($payload);
    }

    /**
     * Build the payload and update template
     *
     * @param string $templateId
     * @param TemplateInterface $template
     * @return array
     * @throws \Exception
     */
    public function updateTemplate(string $templateId, TemplateInterface $template): array
    {
        $payload = $this->payloadBuilder->build($template);
        return $this->templateApi->updateTemplate($templateId, $payload);
    }
}
