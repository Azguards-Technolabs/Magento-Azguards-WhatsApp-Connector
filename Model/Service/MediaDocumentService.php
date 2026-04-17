<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;

class MediaDocumentService
{
    private Curl $curl;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private ApiHelper $apiHelper;

    const XML_PATH_API_BASE_URL = 'whatsappconnect/general/api_base_url';
    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ApiHelper $apiHelper
    ) {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
    }

    private function getBaseUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_BASE_URL, ScopeInterface::SCOPE_STORE) ?? 'https://dev-api.bizzupapp.com';
    }

    private function getToken(): string
    {
        $token = $this->apiHelper->getToken();
        if (!$token) {
            $token = $this->apiHelper->getConnectorAuthentication();
        }

        return is_string($token) ? $token : '';
    }

    /**
     * @param string $name
     * @param string $contentType
     * @return array|null
     */
    public function createDocument(string $name, string $contentType): ?array
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
            $this->logger->info('MediaDocumentService: Creating document', [
                'url' => $url,
                'payload' => $payload,
                'token_present' => $this->getToken() !== ''
            ]);
            $this->curl->post($url, json_encode($payload));
            $response = json_decode($this->curl->getBody(), true);
            $this->logger->info('MediaDocumentService: Create document response', [
                'status' => method_exists($this->curl, 'getStatus') ? $this->curl->getStatus() : null,
                'response' => $response
            ]);
            $id = $response['id']
                ?? $response['docId']
                ?? $response['result']['id']
                ?? $response['result']['docId']
                ?? null;

            $preSignLink = $response['preSignLink']
                ?? $response['result']['preSignLink']
                ?? null;

            if ($id && $preSignLink) {
                return [
                    'id' => $id,
                    'preSignLink' => $preSignLink
                ];
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->error('MediaDocumentService: Create document failed', [
                'url' => $url,
                'name' => $name,
                'content_type' => $contentType,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @param string $url
     * @param string $filePath
     * @param string $contentType
     * @return bool
     */
    public function uploadFileToS3(string $url, string $filePath, string $contentType): bool
    {
        $this->logger->info('MediaDocumentService: Uploading file to S3', [
            'url' => $url,
            'file_path' => $filePath,
            'content_type' => $contentType
        ]);

        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new \Exception("Could not open file for reading: $filePath");
            }
            $size = filesize($filePath);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, $handle);
            curl_setopt($ch, CURLOPT_INFILESIZE, $size);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: $contentType"
            ]);

            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($handle);

            $this->logger->info('MediaDocumentService: S3 upload finished', [
                'status' => $status,
                'result' => $result
            ]);

            return $status >= 200 && $status < 300;
        } catch (\Exception $e) {
            $this->logger->error('MediaDocumentService: S3 upload failed', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getPreviewLink(string $documentId, bool $retry = true): ?string
    {
        $url = rtrim($this->getBaseUrl(), '/') . '/data-manager-service/v1/document/' . $documentId . '?fetchPreviewLink=true';

        $maxRetries = $retry ? 6 : 1;
        $retryDelay = 2; // seconds

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $this->curl->addHeader("Authorization", "Bearer " . $this->getToken());
                $this->curl->addHeader("Accept", "application/json");
                $this->logger->info("MediaDocumentService: Fetching preview link (Attempt " . ($i + 1) . ")", [
                    'url' => $url,
                    'document_id' => $documentId,
                    'token_present' => $this->getToken() !== ''
                ]);
                $this->curl->get($url);
                $response = json_decode($this->curl->getBody(), true);
                
                $this->logger->info('MediaDocumentService: Preview link response', [
                    'status' => method_exists($this->curl, 'getStatus') ? $this->curl->getStatus() : null,
                    'document_id' => $documentId,
                    'response' => $response
                ]);

                $previewLink = $response['previewLink']
                    ?? $response['result']['previewLink']
                    ?? $response['result']['preSignLink']
                    ?? $response['preSignLink']
                    ?? null;

                if ($previewLink) {
                    return $previewLink;
                }

                // If message says not uploaded yet, we wait and retry
                $message = $response['message'] ?? $response['result']['message'] ?? '';
                if (strpos($message, 'Document not uploaded yet') !== false) {
                    $waitTime = $retryDelay + $i;
                    $this->logger->info("MediaDocumentService: Document not ready yet, retrying in {$waitTime}s...");
                    sleep($waitTime);
                    continue;
                }

                if ($i < $maxRetries - 1) {
                    $waitTime = $retryDelay + $i;
                    $this->logger->info("MediaDocumentService: Preview link missing, retrying in {$waitTime}s...", [
                        'document_id' => $documentId
                    ]);
                    sleep($waitTime);
                    continue;
                }

                return null;
            } catch (\Exception $e) {
                $this->logger->error('MediaDocumentService: Fetch preview link failed', [
                    'document_id' => $documentId,
                    'url' => $url,
                    'message' => $e->getMessage()
                ]);
                if ($i < $maxRetries - 1) {
                    sleep($retryDelay + $i);
                    continue;
                }
                return null;
            }
        }

        $this->logger->warning('MediaDocumentService: Preview link unavailable after retries', [
            'document_id' => $documentId,
            'attempts' => $maxRetries
        ]);

        return null;
    }
}
