<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class MediaDocumentService
{
    private Curl $curl;
    private ScopeConfigInterface $scopeConfig;

    const XML_PATH_API_BASE_URL = 'whatsappconnect/general/api_base_url';
    const XML_PATH_ACCESS_TOKEN = 'whatsappconnect/general/access_token';

    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }

    private function getBaseUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_BASE_URL, ScopeInterface::SCOPE_STORE) ?? 'https://dev-api.bizzupapp.com';
    }

    private function getToken(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE) ?? '';
    }

    public function createDocument(string $name, string $contentType): ?string
    {
        $url = rtrim($this->getBaseUrl(), '/') . '/data-manager-service/v1/document';

        $payload = [
            "name" => $name,
            "dataSetName" => "TEMPLATE_MEDIA",
            "contentType" => $contentType
        ];

        $this->curl->addHeader("Authorization", "Bearer " . $this->getToken());
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("Accept", "application/json");

        try {
            $this->curl->post($url, json_encode($payload));
            $response = json_decode($this->curl->getBody(), true);
            return $response['id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPreviewLink(string $documentId): ?string
    {
        $url = rtrim($this->getBaseUrl(), '/') . '/data-manager-service/v1/document/' . $documentId . '?fetchPreviewLink=true';

        $this->curl->addHeader("Authorization", "Bearer " . $this->getToken());
        $this->curl->addHeader("Accept", "application/json");

        try {
            $this->curl->get($url);
            $response = json_decode($this->curl->getBody(), true);
            return $response['previewLink'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
