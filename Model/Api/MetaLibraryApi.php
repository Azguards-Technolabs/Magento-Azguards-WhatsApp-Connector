<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Api;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class MetaLibraryApi
{
    private const API_URL = 'https://graph.facebook.com/v22.0/message_template_library';
    private const XML_PATH_META_ACCESS_TOKEN = 'whatsApp_conector/general/meta_access_token';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Curl $curl
     * @param Json $json
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        Json $json,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Fetch template from Meta Library by name and language
     *
     * @param string $name
     * @param string $language
     * @return array
     * @throws \Exception
     */
    public function fetchTemplate(string $name, string $language = 'en_US'): array
    {
        $token = $this->getMetaAccessToken();
        if (!$token) {
            throw new \Exception(__('Meta Access Token is not configured in System Configuration.'));
        }

        $url = self::API_URL . '?' . http_build_query([
            'language' => $language,
            'name' => $name
        ]);

        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]);

        $this->curl->setOption(CURLOPT_TIMEOUT, 30);

        $this->logger->info('MetaLibraryApi: Fetching template', ['url' => $url]);

        try {
            $this->curl->get($url);
            $responseBody = $this->curl->getBody();
            $status = $this->curl->getStatus();

            $response = $this->json->unserialize($responseBody);

            if ($status !== 200) {
                $errorMessage = $response['error']['message'] ?? __('Unknown error from Meta API');
                throw new \Exception(__('Meta API Error (%1): %2', $status, $errorMessage));
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('MetaLibraryApi Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Meta Access Token from configuration
     *
     * @return string|null
     */
    private function getMetaAccessToken(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_META_ACCESS_TOKEN,
            ScopeInterface::SCOPE_STORE
        );
    }
}
