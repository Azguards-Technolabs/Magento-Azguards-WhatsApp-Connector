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
    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param Json $json
     * @param ScopeConfigInterface $scopeConfig
     * @param ApiHelper $apiHelper
     */
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
     * Execute an API request using the centralized ApiHelper wrapper.
     *
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @return array
     * @throws \RuntimeException
     *
     * Execute API request using the centralized ApiHelper wrapper
     */
    private function doRequest(string $method, string $url, ?array $data = null): array
    {
        $response = $this->apiHelper->callApi($url, $method, $data, 'TemplateApi');

        if (($response['success'] ?? true) === false && !empty($response['message'])) {
            $errorMessage = $this->apiHelper->extractErrorMessage($response);
            if ($errorMessage === 'Unknown API Error') {
                $errorMessage = (string)$response['message'];
            }
            throw new \RuntimeException('WhatsApp API transport error: ' . $errorMessage);
        }

        $status = $this->apiHelper->getCurlStatus();

        if ($status < 200 || $status >= 300) {
            $errorMessage = $this->apiHelper->extractErrorMessage($response);
            throw new \RuntimeException("WhatsApp API Error ($status): $errorMessage");
        }

        return $response;
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
            $total = $decoded['result']['total']
                ?? $decoded['result']['totalRecords']
                ?? $decoded['result']['totalCount']
                ?? $decoded['result']['count']
                ?? $decoded['result']['total_count']
                ?? count($items);
        } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
            $items = $decoded['data'];
            $total = $decoded['total']
                ?? $decoded['meta']['total']
                ?? $decoded['totalRecords']
                ?? $decoded['totalCount']
                ?? $decoded['count']
                ?? count($items);
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
            // Let's use a safer approach:
            $currentPageOffset = ($page > 0) ? ($page - 1) * $limit : 0;
            if ($page === 0) {
                $currentPageOffset = 0;
            }

            $fetched = $currentPageOffset + count($items);
            if (isset($totalVal) && $totalVal > 0) {
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
