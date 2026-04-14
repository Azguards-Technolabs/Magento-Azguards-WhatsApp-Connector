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
     * Get Template API URL
     *
     * @return string
     */
    private function getApiUrl(): string
    {
        return $this->apiHelper->templateApiUrl();
    }

    /**
     * Get authentication token (refreshes automatically when missing)
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        return $this->apiHelper->getOrRefreshToken();
    }

    /**
     * Execute API request with detailed logging and automatic 401 token refresh
     *
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @return array
     * @throws \Exception
     */
    private function doRequest(string $method, string $url, ?array $data = null): array
    {
        return $this->executeRequest($method, $url, $data, false);
    }

    /**
     * Internal request executor; retries once on 401 with a fresh token.
     *
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @param bool $isRetry
     * @return array
     * @throws \Exception
     */
    private function executeRequest(string $method, string $url, ?array $data, bool $isRetry): array
    {
        $payload = $data ? $this->json->serialize($data) : null;
        $token   = $isRetry ? $this->apiHelper->getOrRefreshToken(true) : $this->getAuthToken();

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
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
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            $this->logger->info("- Status: $status");

            if ($error) {
                $this->logger->error("- Curl Error: $error");
                throw new \Exception("Curl Error: $error");
            }

            // Auto-retry once on 401 (expired token)
            if ($status === 401 && !$isRetry) {
                $this->logger->info("- 401 Unauthorized: refreshing token and retrying…");
                return $this->executeRequest($method, $url, $data, true);
            }

            if ($status >= 200 && $status < 300) {
                $this->logger->info("- Response (Truncated): " . substr((string)$response, 0, 500));
            } else {
                $this->logger->info("- Response: $response");
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
        $decoded = $this->doRequest('GET', $this->getApiUrl());
        return $this->normalizeResponse($decoded);
    }

    /**
     * Get paginated templates from API
     *
     * @param int $page   1-based page number
     * @param int $limit  items per page (default 100)
     * @return array      ['data' => [...], 'total' => int, 'hasMore' => bool]
     * @throws \Exception
     */
    public function getTemplatesPaginated(int $page = 1, int $limit = 100): array
    {
        $baseUrl = rtrim($this->getApiUrl(), '/');
        $url = $baseUrl . '?page=' . $page . '&limit=' . $limit;

        $decoded = $this->doRequest('GET', $url);
        return $this->normalizeResponse($decoded, $page, $limit);
    }

    /**
     * Normalize different response shapes from the API
     *
     * @param array $decoded
     * @param int|null $page
     * @param int|null $limit
     * @return array
     */
    private function normalizeResponse(array $decoded, ?int $page = null, ?int $limit = null): array
    {
        // Extract items and total
        if (isset($decoded['result']['data'])) {
            $items = $decoded['result']['data'];
            $total = $decoded['result']['total'] ?? count($items);
        } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
            $items = $decoded['data'];
            $total = $decoded['total'] ?? $decoded['meta']['total'] ?? count($items);
        } elseif (isset($decoded[0])) {
            $items = $decoded;
            $total = count($items);
        } else {
            $items = [];
            $total = 0;
        }

        $totalVal = (int)$total;
        $hasMore = false;

        if ($page !== null && $limit !== null) {
            $fetched = ($page - 1) * $limit + count($items);
            if (isset($decoded['result']['total']) || isset($decoded['total']) || isset($decoded['meta']['total'])) {
                $hasMore = $fetched < $totalVal;
            } else {
                $hasMore = !empty($items) && count($items) >= $limit;
            }
        }

        return [
            'data'    => $items,
            'total'   => $totalVal,
            'hasMore' => $hasMore,
        ];
    }
}
