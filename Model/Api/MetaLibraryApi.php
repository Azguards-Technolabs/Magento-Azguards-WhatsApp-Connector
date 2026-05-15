<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Api;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class MetaLibraryApi
{
    private const API_URL = 'https://graph.facebook.com/v22.0/message_template_library';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Curl $curl
     * @param Json $json
     * @param ApiHelper $apiHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        Json $json,
        ApiHelper $apiHelper,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->apiHelper = $apiHelper;
        $this->logger = $logger;
    }

    /**
     * Fetch template from Meta Library by name and language
     *
     * @param string $name
     * @param string $language
     * @return array
     * @throws LocalizedException
     */
    public function fetchTemplate(string $name, string $language = 'en_US'): array
    {
        $token = $this->apiHelper->getOrRefreshToken();
        if (!$token) {
            throw new LocalizedException(__('Unable to retrieve authentication token.'));
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
                $errorMessage = $response['error']['message']
                    ?? $response['message']
                    ?? __('Unknown error from Meta API');
                throw new LocalizedException(__('Meta API Error (%1): %2', $status, $errorMessage));
            }

            return $response;
        } catch (LocalizedException $e) {
            $this->logger->error('MetaLibraryApi Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
