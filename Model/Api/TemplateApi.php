<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Api;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;

class TemplateApi
{
    private $curl;
    private $logger;
    private $json;
    private $scopeConfig;
    private $apiHelper;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        Json $json,
        ScopeConfigInterface $scopeConfig,
        ApiHelper $apiHelper
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Get API URL
     *
     * @return string
     */
    private function getApiUrl(): string
    {
        return (string)$this->scopeConfig->getValue('whatsApp_conector/general/template_api_url');
    }

    /**
     * Get authentication token
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        $token = $this->apiHelper->getToken();
        if (!$token) {
            $token = $this->apiHelper->getConnectorAuthentication();
        }
        return is_string($token) ? $token : '';
    }

    /**
     * Execute API request with detailed logging
     *
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @return array
     * @throws \Exception
     */
    private function doRequest(string $method, string $url, ?array $data = null): array
    {
        $payload = $data ? $this->json->serialize($data) : null;
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getAuthToken()
        ];

        $this->logger->info("API Request Details:");
        $this->logger->info("- Method: $method");
        $this->logger->info("- URL: $url");
        if ($payload) {
            $this->logger->info("- Payload: $payload");
        }

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if ($payload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $this->logger->info("- Status: $status");
            if ($status >= 200 && $status < 300) {
                // Log only first 500 chars of success for sync efficiency
                $this->logger->info("- Response (Truncated): " . substr((string)$response, 0, 500));
            } else {
                $this->logger->info("- Response: $response");
            }

            if ($error) {
                $this->logger->error("- Curl Error: $error");
                throw new \Exception("Curl Error: $error");
            }

            $decodedResponse = json_decode((string)$response, true);
            if ($status < 200 || $status >= 300) {
                $errorMessage = $decodedResponse['error']['message'] 
                    ?? $decodedResponse['message'] 
                    ?? $decodedResponse['error'] 
                    ?? $decodedResponse['status'] 
                    ?? 'Unknown Error';
                
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                }
                
                throw new \Exception("ERP API Error ($status): $errorMessage");
            }

            return $decodedResponse ?: [];
        } catch (\Exception $e) {
            $this->logger->error("API request failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create template in API
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function createTemplate(array $data): array
    {
        return $this->doRequest('POST', $this->getApiUrl(), $data);
    }

    /**
     * Update template in API
     *
     * @param string $templateId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateTemplate(string $templateId, array $data): array
    {
        $url = $this->getApiUrl() . '/' . urlencode($templateId);
        return $this->doRequest('PUT', $url, $data);
    }

    /**
     * Delete template from API
     *
     * @param string $templateId
     * @return bool
     * @throws \Exception
     */
    public function deleteTemplate(string $templateId): bool
    {
        $url = $this->getApiUrl() . '/' . urlencode($templateId);
        $this->doRequest('DELETE', $url);
        return true;
    }

    /**
     * Get templates from API
     *
     * @return array
     * @throws \Exception
     */
    public function getTemplates(): array
    {
        return $this->doRequest('GET', $this->getApiUrl());
    }

    /**
     * Get paginated templates from API
     *
     * @param int $page   1-based page number
     * @param int $limit  items per page (default 10)
     * @return array      ['data' => [...], 'total' => int, 'hasMore' => bool]
     * @throws \Exception
     */
    public function getTemplatesPaginated(int $page = 1, int $limit = 10): array
    {
        $baseUrl = rtrim($this->getApiUrl(), '/');
        $url = $baseUrl . '?page=' . $page . '&limit=' . $limit;

        $decoded = $this->doRequest('GET', $url);

        // Normalise different response shapes
        if (isset($decoded['result']['data'])) {
            $items = $decoded['result']['data'];
            $total = $decoded['result']['total'] ?? count($items);
        } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
            $items = $decoded['data'];
            $total = $decoded['total'] ?? $decoded['meta']['total'] ?? count($items);
        } elseif (is_array($decoded) && isset($decoded[0])) {
            $items = $decoded;
            $total = count($items);
        } else {
            $items = [];
            $total = 0;
        }

        $fetched = ($page - 1) * $limit + count($items);
        $totalAvailable = isset($decoded['result']['total']) || 
                         isset($decoded['total']) || 
                         isset($decoded['meta']['total']);
        
        if ($totalAvailable) {
            $hasMore = $fetched < (int)$total;
        } else {
            $hasMore = !empty($items) && count($items) === $limit;
        }

        return [
            'data'    => $items,
            'total'   => (int)$total,
            'hasMore' => $hasMore,
        ];
    }
}
