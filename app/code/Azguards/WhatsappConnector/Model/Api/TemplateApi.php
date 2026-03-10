<?php
namespace Azguards\WhatsappConnector\Model\Api;

use Azguards\WhatsappConnector\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class TemplateApi
{
    const XML_PATH_AUTH_ENDPOINT = 'whatsapp_connector/general/auth_endpoint';
    const XML_PATH_TEMPLATE_ENDPOINT = 'whatsapp_connector/general/template_endpoint';
    const XML_PATH_CLIENT_ID = 'whatsapp_connector/general/client_id';
    const XML_PATH_CLIENT_SECRET = 'whatsapp_connector/general/client_secret';
    const XML_PATH_GRANT_TYPE = 'whatsapp_connector/general/grant_type';

    protected $logger;
    protected $scopeConfig;
    protected $encryptor;
    protected $token = null;

    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    protected function getApiUrl()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TEMPLATE_ENDPOINT, ScopeInterface::SCOPE_STORE);
    }

    protected function authenticate()
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $authEndpoint = $this->scopeConfig->getValue(self::XML_PATH_AUTH_ENDPOINT, ScopeInterface::SCOPE_STORE);
        $clientId = $this->scopeConfig->getValue(self::XML_PATH_CLIENT_ID, ScopeInterface::SCOPE_STORE);
        $encryptedSecret = $this->scopeConfig->getValue(self::XML_PATH_CLIENT_SECRET, ScopeInterface::SCOPE_STORE);
        $clientSecret = $this->encryptor->decrypt($encryptedSecret);
        $grantType = $this->scopeConfig->getValue(self::XML_PATH_GRANT_TYPE, ScopeInterface::SCOPE_STORE);

        if (!$authEndpoint || !$clientId || !$clientSecret) {
            throw new \Exception('WhatsApp API credentials are not fully configured.');
        }

        $payload = json_encode([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => $grantType
        ]);

        $ch = curl_init($authEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logger->info('Auth API Response [' . $status . ']: ' . $response);

        if ($status !== 200) {
            throw new \Exception('Failed to authenticate with WhatsApp ERP API.');
        }

        $responseData = json_decode($response, true);
        if (isset($responseData['access_token'])) {
            $this->token = $responseData['access_token'];
            return $this->token;
        }

        throw new \Exception('Authentication token not found in response.');
    }

    protected function getHeaders()
    {
        $token = $this->authenticate();
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
    }

    protected function makeRequest($url, $method, $data = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logger->info("API Response [$status]: " . $response);

        return ['status' => $status, 'body' => json_decode($response, true)];
    }

    public function createTemplate(array $templateData)
    {
        $apiUrl = $this->getApiUrl();
        if (!$apiUrl) throw new \Exception('WhatsApp Template API endpoint is not configured.');

        $this->logger->info('Creating template via API: ' . json_encode($templateData));
        $result = $this->makeRequest($apiUrl, 'POST', $templateData);

        if ($result['status'] !== 200 && $result['status'] !== 201) {
            throw new \Exception('Failed to create template. API returned status: ' . $result['status']);
        }

        return $result['body'];
    }

    public function updateTemplate($templateId, array $templateData)
    {
        $apiUrl = $this->getApiUrl();
        if (!$apiUrl) throw new \Exception('WhatsApp Template API endpoint is not configured.');

        $this->logger->info("Updating template $templateId via API: " . json_encode($templateData));
        $result = $this->makeRequest($apiUrl . '/' . $templateId, 'PUT', $templateData);

        if ($result['status'] !== 200) {
            throw new \Exception('Failed to update template. API returned status: ' . $result['status']);
        }

        return $result['body'];
    }

    public function deleteTemplate($templateId)
    {
        $apiUrl = $this->getApiUrl();
        if (!$apiUrl) throw new \Exception('WhatsApp Template API endpoint is not configured.');

        $this->logger->info("Deleting template $templateId via API");
        $result = $this->makeRequest($apiUrl . '/' . $templateId, 'DELETE');

        if ($result['status'] !== 200 && $result['status'] !== 204) {
            throw new \Exception('Failed to delete template. API returned status: ' . $result['status']);
        }

        return true;
    }

    public function getTemplates()
    {
        $apiUrl = $this->getApiUrl();
        if (!$apiUrl) throw new \Exception('WhatsApp Template API endpoint is not configured.');

        $this->logger->info('Fetching templates via API');
        $result = $this->makeRequest($apiUrl, 'GET');

        if ($result['status'] !== 200) {
            throw new \Exception('Failed to fetch templates. API returned status: ' . $result['status']);
        }

        return $result['body'];
    }
}
