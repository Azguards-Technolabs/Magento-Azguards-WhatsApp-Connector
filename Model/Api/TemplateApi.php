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
     * Set default headers
     *
     * @return void
     */
    private function setHeaders(): void
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Authorization', 'Bearer ' . $this->getAuthToken());
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
        $this->logger->info('API Request (Create Template): ' . $this->json->serialize($data));

        try {
            $this->setHeaders();
            $this->curl->post($this->getApiUrl(), $this->json->serialize($data));
            $response = $this->curl->getBody();
            $status = $this->curl->getStatus();

            $this->logger->info('API Response: ' . $response);

            if ($status >= 200 && $status < 300) {
                return $this->json->unserialize($response);
            }

            throw new \Exception('Failed to create template. Status: ' . $status);
        } catch (\Exception $e) {
            $this->logger->error('API Error (Create Template): ' . $e->getMessage());
            throw $e;
        }
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
        $this->logger->info('API Request (Update Template): ' . $this->json->serialize($data));

        try {
            // Using low level curl options because Magento HTTP Client `get()` resets method to GET
            // and `post()` sets it to POST. We need to manually use curl_exec to properly handle PUT/DELETE

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->json->serialize($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getAuthToken()
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $this->logger->info('API Response: ' . $response);

            if ($status >= 200 && $status < 300) {
                return $this->json->unserialize($response);
            }

            throw new \Exception('Failed to update template. Status: ' . $status . ' Error: ' . $error);
        } catch (\Exception $e) {
            $this->logger->error('API Error (Update Template): ' . $e->getMessage());
            throw $e;
        }
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
        $this->logger->info('API Request (Delete Template): ' . $templateId);

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getAuthToken()
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $this->logger->info('API Response: ' . $response);

            if ($status >= 200 && $status < 300) {
                return true;
            }

            throw new \Exception('Failed to delete template. Status: ' . $status . ' Error: ' . $error);
        } catch (\Exception $e) {
            $this->logger->error('API Error (Delete Template): ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get templates from API
     *
     * @return array
     * @throws \Exception
     */
    public function getTemplates(): array
    {
        $this->logger->info('API Request (Get Templates)');

        try {
            $this->setHeaders();
            $this->curl->setOption(CURLOPT_TIMEOUT, 60);
            $this->curl->get($this->getApiUrl());
            $response = $this->curl->getBody();
            $status = $this->curl->getStatus();

            if ($status >= 200 && $status < 300) {
                return $this->json->unserialize($response);
            }

            throw new \Exception('Failed to get templates. Status: ' . $status);
        } catch (\Exception $e) {
            $this->logger->error('API Error (Get Templates): ' . $e->getMessage());
            throw $e;
        }
    }
}
