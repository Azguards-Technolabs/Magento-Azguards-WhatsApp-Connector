<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Psr\Log\LoggerInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\HTTP\Client\Curl;

class MediaDocumentService
{
    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var File
     */
    private File $fileDriver;

    /**
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param ApiHelper $apiHelper
     * @param File $fileDriver
     */
    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        ApiHelper $apiHelper,
        File $fileDriver
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Get API base URL.
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        return rtrim($this->apiHelper->baseUrl(), '/');
    }

    /**
     * Get authentication token.
     *
     * @return string
     */
    private function getToken(): string
    {
        $token = $this->apiHelper->getToken();
        if (!$token) {
            $token = $this->apiHelper->getConnectorAuthentication();
        }

        return is_string($token) ? $token : '';
    }

    /**
     * Create a media document entry in the external data-manager service.
     *
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
            $response = json_decode($this->curl->getBody(), true) ?: [];
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

            if (!$id || !$preSignLink) {
                $status = method_exists($this->curl, 'getStatus') ? $this->curl->getStatus() : null;
                $errorMessage = $this->apiHelper->extractErrorMessage($response);
                $this->logger->warning("MediaDocumentService: API response incomplete ($status): $errorMessage", [
                    'response' => $response
                ]);
            }

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
     * Upload the local file to the provided pre-signed URL.
     *
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
            $contents = $this->fileDriver->fileGetContents($filePath);

            $this->curl->setOptions([
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ["Content-Type: $contentType"],
            ]);
            $this->curl->post($url, $contents);
            $result = $this->curl->getBody();
            $status = method_exists($this->curl, 'getStatus') ? $this->curl->getStatus() : 0;

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

    /**
     * Get the preview link for an uploaded document.
     *
     * @param string $documentId
     * @param bool $retry
     * @return string|null
     */
    public function getPreviewLink(string $documentId, bool $retry = true): ?string
    {
        $url = rtrim($this->getBaseUrl(), '/')
            . '/data-manager-service/v1/document/' . $documentId . '?fetchPreviewLink=true';

        $maxRetries = $retry ? 6 : 1;

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
                $response = json_decode($this->curl->getBody(), true) ?: [];

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

                if (!$previewLink && $i === ($maxRetries - 1)) {
                    $errorMessage = $this->apiHelper->extractErrorMessage($response);
                    $this->logger->warning("MediaDocumentService: Failed to extract previewLink: $errorMessage");
                }

                if ($previewLink) {
                    return $previewLink;
                }

                // If message says not uploaded yet, we wait and retry
                $message = $response['message'] ?? $response['result']['message'] ?? '';
                if (strpos($message, 'Document not uploaded yet') !== false) {
                    $this->logger->info(
                        'MediaDocumentService: Document not ready yet, retrying request.',
                        ['attempt' => $i + 1, 'document_id' => $documentId]
                    );
                    continue;
                }

                if ($i < $maxRetries - 1) {
                    $this->logger->info('MediaDocumentService: Preview link missing, retrying request.', [
                        'attempt' => $i + 1,
                        'document_id' => $documentId
                    ]);
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
