<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class MetaServiceAdapter
{
    private const XML_PATH_ACCESS_TOKEN = 'whatsapp_connect/general/access_token';
    private const XML_PATH_WABA_ID = 'whatsapp_connect/general/waba_id';

    private $curl;
    private $logger;
    private $json;
    private $scopeConfig;

    private $graphApiUrl = 'https://graph.facebook.com/v21.0';

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        Json $json,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
    }

    private function getAccessToken(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);
    }

    private function getWabaId(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_WABA_ID, ScopeInterface::SCOPE_STORE);
    }

    private function setHeaders(): void
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Bearer ' . $this->getAccessToken());
    }

    public function createTemplate(array $payload): array
    {
        $url = "{$this->graphApiUrl}/{$this->getWabaId()}/message_templates";
        return $this->executeWithRetry($url, 'POST', $payload);
    }

    public function fetchLibraryTemplate(string $libraryTemplateId): array
    {
        $url = "{$this->graphApiUrl}/{$libraryTemplateId}";
        return $this->executeWithRetry($url, 'GET');
    }

    public function fetchMediaHeaderHandle(array $mediaData): string
    {
        $this->logger->info('Fetching media header handle for: ' . $this->json->serialize($mediaData));
        return 'dummy_media_handle_' . uniqid();
    }

    private function executeWithRetry(string $url, string $method, array $payload = []): array
    {
        try {
            return $this->executeRequest($url, $method, $payload);
        } catch (\Exception $e) {
            if ($this->isTokenError($e->getMessage())) {
                $this->refreshAccessToken();
                return $this->executeRequest($url, $method, $payload);
            }
            throw $e;
        }
    }

    private function executeRequest(string $url, string $method, array $payload = []): array
    {
        $this->setHeaders();
        if ($method === 'POST') {
            $this->curl->post($url, $this->json->serialize($payload));
        } else {
            $this->curl->get($url);
        }

        $response = $this->curl->getBody();
        $status = $this->curl->getStatus();

        if ($status >= 200 && $status < 300) {
            return $this->json->unserialize($response);
        }

        throw new \Exception("Meta API Error: Status {$status}, Response: {$response}");
    }

    private function isTokenError(string $errorMessage): bool
    {
        return strpos($errorMessage, 'Error validating access token') !== false ||
               strpos($errorMessage, 'Expired') !== false;
    }

    private function refreshAccessToken(): void
    {
        $this->logger->info('Refreshing Meta Access Token...');
        // Logic to refresh token via TokenService could be implemented here
    }
}
