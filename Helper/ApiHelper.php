<?php

namespace Azguards\WhatsAppConnect\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Magento\Framework\App\CacheInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Azguards\WhatsAppConnect\Model\Source\CountryCallingCodes;

class ApiHelper extends AbstractHelper
{
    // Config paths
    public const XML_PATH_BASE_URL            = 'whatsApp_conector/general/base_url';
    public const XML_PATH_MESSAGE_BASE_URL    = 'whatsApp_conector/general/message_base_url';
    public const XML_PATH_AUTHENTICATION_API_URL = 'whatsApp_conector/general/authentication_api_url';
    public const XML_PATH_CLIENT_ID           = 'whatsApp_conector/general/client_id';
    public const XML_PATH_CLIENT_SECRET_KEY   = 'whatsApp_conector/general/client_secret_key';
    public const XML_PATH_GRANT_TYPE          = 'whatsApp_conector/general/grant_type';
    public const XML_PATH_ENABLED             = 'whatsApp_conector/general/enable';

    // API endpoint paths (appended to base_url, which must include version prefix e.g. /meta-service/v1)
    public const ENDPOINT_TEMPLATES = '/template';
    public const ENDPOINT_CONTACT   = '/api/v1/contacts';
    public const ENDPOINT_MESSAGE   = '/api/v1/message/send';
    public const ENDPOINT_LANGUAGE  = '/language';
    // public const COOKIE_NAME = 'whatsApp-conector';
    public const COOKIE_NAME = 'wa_auth_token';
    public const CACHE_TAG = 'whatsapp_templates';
    public const CACHE_LIFETIME = 86400; // 24 hours
    private const CONTACT_ID_CACHE_PREFIX = 'wa_contact_id_';
    private const XML_PATH_DEBUG_LOGGING = 'whatsApp_conector/general/debug_logging';

     /**
      * @var Curl
      */
    protected $curl;
     /**
      * @var ScopeConfig
      */
    protected $scopeConfig;
     /**
      * @var StoreManager
      */
    protected $storeManager;
     /**
      * @var CookieManager
      */
    protected $cookieManager;
     /**
      * @var CookieMetadataFactory
      */
    protected $cookieMetadataFactory;
     /**
      * @var SessionManager
      */
    protected $sessionManager;
     /**
      * @var Logger
      */
    protected $logger;
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var WhatsAppEventLogger
     */
    protected $eventLogger;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerResource
     */
    protected $customerResource;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var CountryCallingCodes
     */
    protected $countryCallingCodes;

    /**
     * ApiHelper construct
     *
     * @param Context $context
     * @param Curl $curl
     * @param StoreManagerInterface $storeManager
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param CacheInterface $cache
     */
    public function __construct(
        Context $context,
        Curl $curl,
        StoreManagerInterface $storeManager,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        Logger $logger,
        WhatsAppEventLogger $eventLogger,
        ScopeConfigInterface $scopeConfig,
        CacheInterface $cache,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        DateTime $dateTime,
        CountryCallingCodes $countryCallingCodes
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->eventLogger = $eventLogger;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->dateTime = $dateTime;
        $this->countryCallingCodes = $countryCallingCodes;
    }

    /**
     * Check if module is enabled in configuration
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @var array
     */
    protected $_templatesCache = [];

    /**
     * Fetch Templates
     *
     * @param int|null $limit
     * @return array
     */
    public function fetchTemplates($limit = 3)
    {
        try {
            if (isset($this->_templatesCache[$limit])) {
                return $this->_templatesCache[$limit];
            }

            $cacheKey = self::CACHE_TAG . '_' . $limit;
            $cachedData = $this->cache->load($cacheKey);
            if ($cachedData) {
                $response = json_decode($cachedData, true);
                if ($response) {
                    $this->_templatesCache[$limit] = $response;
                    return $response;
                }
            }

            $url = $this->templateApiUrl();
            $accessToken = $this->getOrRefreshToken();

            $doCall = function ($token) use ($url) {
                $headers = [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ];
                $this->curl->setHeaders($headers);
                $this->curl->setOption(CURLOPT_TIMEOUT, 10);
                $this->curl->get($url);
                return $headers;
            };

            $headers = $doCall($accessToken);

            // Auto-retry on 401 (expired token)
            if ($this->curl->getStatus() === 401) {
                $accessToken = $this->getOrRefreshToken(true);
                $headers = $doCall($accessToken);
            }

            $response = json_decode($this->curl->getBody(), true);

            if ($limit !== null && isset($response['result']['data']) && is_array($response['result']['data'])) {
                $response['result']['data'] = array_slice($response['result']['data'], 0, $limit);
            }
            if (!empty($response['result']['data'])) {
                $this->cache->save(
                    json_encode($response),
                    $cacheKey,
                    [self::CACHE_TAG],
                    self::CACHE_LIFETIME
                );
            }

            if ($this->isDebugLoggingEnabled()) {
                $this->logger->loggedAsInfoData(
                    $url,
                    'fetchTemplates',
                    $response['Message'] ?? 'Success',
                    $headers,
                    [],
                    $response
                );
            }

            $this->_templatesCache[$limit] = $response;
            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch contact details from API
     */
    public function fetchContactDetails($data): array
    {
        $payload = is_string($data) ? json_decode($data, true) : $data;
        return $this->callApi($this->contactApiUrl(), 'POST', $payload, 'fetchContactDetails');
    }

    /**
     * Get country calling codes by country code
     *
     * @param string $countrycode
     * @return string
     */
    public function getCountryCallingCodes($countrycode)
    {
        return $this->countryCallingCodes->getCallingCode((string)$countrycode);
    }

    /**
     * Get Customer Details
     *
     * @param array|string|int|object $order
     * @param array|string|int $userTempaletId
     * @return void
     */
    public function getCustomerDetails($order, $userTempaletId)
    {
        if (!$order) {
            $this->logger->error("Order object is missing.");
            return null;
        }

        // Fetch customer details safely
        $firstName = $order->getCustomerFirstname() ?? 'Guest';
        $lastName = $order->getCustomerLastname() ?? '';
        $email = $order->getCustomerEmail() ?? 'no-email@example.com';

        // Get Billing Address
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress) {
            $this->logger->error(
                "Billing address not found for order ID: " . $order->getId()
            );
            return [
                "templateId" => $userTempaletId,
                "firstName" => $firstName,
                "lastName" => $lastName,
                "countryCode" => '00', // Default unknown country code
                "mobileNumber" => '0000000000', // Default unknown number
                "imageURL" => "",
                "email" => $email,
                "businessName" => "Your Store Name",
                "website" => "https://yourstore.com"
            ];
        }

        // Get Country ID and Mobile Number
        $countryId = $billingAddress->getCountryId() ?? 'XX';
        $mobileNumber = $billingAddress->getTelephone();
        if (empty($mobileNumber)) {
            $mobileNumber = '0000000000';
        }
        // Fetch Country Calling Code
        $countryCode = $this->getCountryCallingCodes($countryId) ?? '00';

        // Prepare Data
        return [
            "templateId" => $userTempaletId,
            "firstName" => $firstName,
            "lastName" => $lastName,
            "countryCode" => $countryCode,
            "mobileNumber" => $mobileNumber,
            "imageURL" => "",
            "email" => $email,
            "businessName" => "Your Store Name",
            "website" => "https://yourstore.com"
        ];
    }

    /**
     * Get template variables by template ID
     *
     * @param string $templateId
     * @return array
     */
    public function getTemplateVariable($templateId)
    {
        $response = $this->fetchTemplates(null);
        $templates = $response['result']['data'] ?? [];
        $templateVariable = [];

        foreach ($templates as $item) {
            if (($item['id'] ?? null) !== $templateId) {
                continue;
            }

            // Check for variables in 'components' array (new structure)
            if (!empty($item['components']) && is_array($item['components'])) {
                $overallOrder = 1;
                foreach ($item['components'] as $component) {
                    if (in_array($component['componentType'] ?? '', ['HEADER', 'BODY'])) {
                        $componentHasVariables = false;
                        // Priority 1: Use 'variables' array if present
                        if (!empty($component['variables']) && is_array($component['variables'])) {
                            foreach ($component['variables'] as $index => $variable) {
                                $type = (string)($variable['defaultValue'] ?? $variable['variableName'] ?? $variable['parameterName'] ?? $variable['name'] ?? ('var_' . ($variable['variablePosition'] ?? ($index + 1))));
                                $templateVariable[] = $this->buildTemplateVariableRow($type, $overallOrder++);
                            }
                            $componentHasVariables = true;
                        }
                        
                        // Priority 2: Extract from 'componentData' if still empty
                        if (!$componentHasVariables && !empty($component['componentData']) && is_string($component['componentData'])) {
                            $extractedVariables = $this->extractVariablesFromText($component['componentData']);
                            foreach ($extractedVariables as $variableName) {
                                $templateVariable[] = $this->buildTemplateVariableRow($variableName, $overallOrder++);
                            }
                        }
                    }
                }
                if (!empty($templateVariable)) {
                    break;
                }
            }

            // Priority 3: Fallback to old top-level structure if still empty
            if (empty($templateVariable)) {
                $sampleValues = $item['templateBodyTextSampleValues']
                    ?? $item['bodyTextSampleValues']
                    ?? $item['body_examples']
                    ?? [];

                if (!empty($sampleValues) && is_array($sampleValues)) {
                    foreach ($sampleValues as $index => $param) {
                        $type = (string)($param['parameterName'] ?? $param['name'] ?? $param['example'] ?? ('var_' . ($index + 1)));
                        $templateVariable[] = $this->buildTemplateVariableRow($type, $index + 1);
                    }
                }
            }

            if (empty($templateVariable)) {
                $bodyText = (string)($item['templateBodyText'] ?? $item['body'] ?? '');
                $extractedVariables = $this->extractVariablesFromText($bodyText);
                foreach ($extractedVariables as $index => $variableName) {
                    $templateVariable[] = $this->buildTemplateVariableRow($variableName, $index + 1);
                }
            }

            break;
        }

        return $templateVariable;
    }

    private function buildTemplateVariableRow(string $type, int $order): array
    {
        $cleanType = trim($type, '{} ');
        $identifierPrefix = 'catalogsearch_fulltext_';

        return [
            'title' => $cleanType,
            'identifier' => $identifierPrefix . $cleanType,
            'order' => $order,
            'type' => $cleanType,
        ];
    }

    private function extractVariablesFromText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        preg_match_all('/\{\{\s*([^}]+?)\s*\}\}/', $text, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $variables = [];
        foreach ($matches[1] as $match) {
            $name = trim((string)$match);
            if ($name !== '' && !in_array($name, $variables, true)) {
                $variables[] = $name;
            }
        }

        return $variables;
    }

    /**
     * Get static templates for testing
     *
     * @return array
     */
    public function getStatiTemplate()
    {
        return [
        [
            "templateName" => "local_kenit_55",
            "templateCategory" => "MARKETING",
            "templateHeaderType" => "TEXT",
            "id" => "e939fc3e-abbf-4d99-9063-21b59588b73a",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "lastname",
                    "parameterValue" => "kenit"
                ],
                [
                    "parameterName" => "firstname",
                    "parameterValue" => "kenit test"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "c9c56177-2079-451c-b982-3dfe40ce9964",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "order_id",
                    "parameterValue" => "Order Id"
                ],
                [
                    "parameterName" => "order_total",
                    "parameterValue" => "Order Total"
                ],[
                    "parameterName" => "order_number",
                    "parameterValue" => "Order Number"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "ba9407d9-1ca8-4161-be18-930ae9682a33",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "invoice_id",
                    "parameterValue" => "Invoice Id"
                ],
                [
                    "parameterName" => "invoice_total",
                    "parameterValue" => "Invoice Total"
                ],[
                    "parameterName" => "invoice_number",
                    "parameterValue" => "Invoice Number"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "48fe321d-a1d4-43e5-b16e-7fe2422c80b1",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "shipment_id",
                    "parameterValue" => "Shipment Id"
                ],
                [
                    "parameterName" => "shipment_total",
                    "parameterValue" => "Shipment Total"
                ],[
                    "parameterName" => "shipment_number",
                    "parameterValue" => "Shipment Number"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "35c6a045-0219-460a-bc7e-67e07ebd7ff6",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "cancel_id",
                    "parameterValue" => "Cancel Id"
                ],
                [
                    "parameterName" => "cancel_total",
                    "parameterValue" => "Cancel Total"
                ],[
                    "parameterName" => "cancel_number",
                    "parameterValue" => "Cancel Number"
                ],[
                    "parameterName" => "order_status",
                    "parameterValue" => "Order Status"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "6eb7eb58-0495-4b86-ba8f-cd425362cabe",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "refund_id",
                    "parameterValue" => "Refund Id"
                ],
                [
                    "parameterName" => "refund_total",
                    "parameterValue" => "Refund Total"
                ],[
                    "parameterName" => "refund_number",
                    "parameterValue" => "Refund Number"
                ],[
                    "parameterName" => "order_status",
                    "parameterValue" => "Order Status"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],
        // You can add more templates here
        ];
    }

    /**
     * Get Customer User Details
     *
     * @param array|string|int|object $customer
     * @param array|string|int $userTempaletId
     * @return void
     */
    public function getCustomerUserDetails($customer, $userTempaletId)
    {
        $firstName = $customer->getFirstname() ?? 'Guest';
        $lastName = $customer->getLastname() ?? '';
        $email = $customer->getEmail() ?? 'no-email@example.com';

        // Get customer default billing address
        $billingAddress = $customer->getDefaultBillingAddress();
        if (!$billingAddress) {
            return [
                "templateId" => $userTempaletId,
                "firstName" => $firstName,
                "lastName" => $lastName,
                "countryCode" => '00', // Default unknown country code
                "mobileNumber" => '0000000000', // Default unknown number
                "imageURL" => "",
                "email" => $email,
                "businessName" => "Your Store Name",
                "website" => "https://yourstore.com"
            ];
        }

        // Get Country ID and Mobile Number
        $countryId = $billingAddress->getCountryId() ?? 'XX';
        $mobileNumber = $billingAddress->getTelephone() ?? '0000000000';
        if (empty($mobileNumber)) {
            $mobileNumber = '0000000000';
        }
        // Get Country Calling Code
        $countryCode = $this->getCountryCallingCodes($countryId) ?? '00';

        // Return structured data
        return [
            "templateId" => $userTempaletId,
            "firstName" => $firstName,
            "lastName" => $lastName,
            "countryCode" => $countryCode,
            "mobileNumber" => $mobileNumber,
            "imageURL" => "",
            "email" => $email,
            "businessName" => "Your Store Name",
            "website" => "https://yourstore.com"
        ];
    }

    /**
     * Send WhatsApp Message via API
     *
     * @param array|string|int $templateId
     * @param array|string|int $tempaletVerible
     * @param array|string|int $requestType
     * @param array|string|int $userDetail
     * @return void
     */
    public function sendMessage($templateId, $tempaletVerible, $requestType, $userDetail)
    {
        return $this->sendTemplateMessage(
            (string)$templateId,
            is_array($tempaletVerible) ? $tempaletVerible : [],
            is_array($userDetail) ? $userDetail : [],
            (string)$requestType
        );
    }

    /**
     * Send WhatsApp template message via API
     */
    public function sendTemplateMessage(
        string $templateId,
        array $placeholderValues,
        array $userDetail,
        string $requestType = 'send_template_message',
        ?string $mediaHandle = null,
        ?string $mediaUrl = null,
        bool $syncContact = true
    ): array {
        if ($templateId === '') {
            return ['success' => false, 'message' => 'Template ID is required'];
        }

        $url = $this->messageApiUrl();
        $components = [];

        // 1. Header component for media
        if ($mediaHandle || $mediaUrl) {
            $mediaComponent = [
                'component_type' => 'HEADER',
                'header_type'    => 'IMAGE', // Default to IMAGE, adjust dynamically if possible
            ];

            if ($mediaUrl) {
                // Infer type from extension to be thorough
                $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                if (in_array($ext, ['mp4', 'avi', 'mov'])) {
                    $mediaComponent['header_type'] = 'VIDEO';
                } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt'])) {
                    $mediaComponent['header_type'] = 'DOCUMENT';
                }
            }

            if ($mediaHandle) {
                $mediaComponent['media'] = ['id' => $mediaHandle];
            } else {
                $mediaComponent['media'] = ['url' => $mediaUrl]; // Fallback to URL mapping
            }
            $components[] = $mediaComponent;
        }

        // 2. Body component for placeholders
        if (!empty($placeholderValues)) {
            $placeholders = [];
            $orderVar = 1;
            foreach ($placeholderValues as $key => $val) {
                $cleanAttributeName = str_replace('catalogsearch_fulltext_', '', (string)$key);
                
                $placeholders[] = [
                    'key'               => (string)$orderVar++,
                    'value'             => is_scalar($val) || $val === null ? (string)$val : json_encode($val),
                    'is_user_attribute' => true,
                    'attribute_name'    => $cleanAttributeName
                ];
            }
            // Ensure order considers preceding components
            $components[] = [
                'component_type'   => 'BODY',
                'component_format' => 'TEXT',
                'order'            => empty($components) ? 1 : count($components) + 1,
                'placeholder'      => $placeholders
            ];
        }


        $countryCode = preg_replace('/\D/', '', (string)($userDetail['countryCode'] ?? ''));
        $phoneNumber = preg_replace('/\D/', '', (string)($userDetail['mobileNumber'] ?? ''));
        $waId = ltrim($countryCode . $phoneNumber, '+');

        if ($waId === '') {
            $this->logger->warning('sendTemplateMessage aborted: missing wa_id (phone number)', [
                'request_type' => $requestType,
                'template_id' => $templateId,
            ]);
            return [
                'success' => false,
                'message' => 'Unable to resolve phone number for wa_id.',
            ];
        }

        $payload = [
            'wa_id'                => $waId,
            'message_type'         => 'template',
            'template'             => [
                'template_id' => $templateId,
                'components'  => $components
            ]
        ];

        $this->eventLogger->logPayload($requestType, $payload, [
            'api_url' => $url,
            'stage'   => 'before_api_call',
        ]);

        // Forced Curl Log for Senior Dev expectations
        $this->logCurlCommand($url, 'POST', [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getOrRefreshToken()
        ], json_encode($payload));

        $response = $this->callApi($url, 'POST', $payload, $requestType);

        $this->eventLogger->logApiResponse($requestType, $response, [
            'api_url'     => $url,
            'http_status' => $this->curl->getStatus(),
        ]);

        $isSuccess = ($response['Result']['status'] ?? '') === 'success'
            || ($response['result']['status'] ?? '') === 'success'
            || isset($response['messages']);

        return [
            'success' => $isSuccess,
            'message' => $response['Result']['message']
                ?? $response['result']['message']
                ?? $response['Message']
                ?? 'Failed to send message',
            'response' => $response,
        ];
    }

    /**
     * Get User DetailData
     *
     * @param array|string|int|object $order
     * @return void
     */
    public function getUserDetailData($order)
    {
        $billingAddress = $order->getBillingAddress();
        $customer = $order->getCustomer();
        $countryId = $billingAddress ? $billingAddress->getCountryId() : '';
        $countryCode = $this->getCountryCallingCodes($countryId) ?? '00';
        $telephoneRaw = $billingAddress ? (string)$billingAddress->getTelephone() : '';
        $telephone = preg_replace('/\D/', '', $telephoneRaw);
        $userDetail = [
            'firstName'     => $billingAddress ? $billingAddress->getFirstname() : '',
            'lastName'      => $billingAddress ? $billingAddress->getLastname() : '',
            'countryCode'   => preg_replace('/\D/', '', (string)$countryCode),
            'mobileNumber'  => $telephone,
            'imageURL'      => 'https://randomuser.me/api/portraits/men/45.jpg', // You can customize this logic
            'email'         => $order->getCustomerEmail(),
            'businessName'  => $order->getBillingAddress() ?
            $order->getBillingAddress()->getCompany() : 'Verma Creations',
            'website'       => $this->storeManager->getStore()->getBaseUrl()
        ];

        return $userDetail;
    }

    /**
     * Get Connector Authentication
     *
     * @param array|string|int|null $url
     * @param array|string|int|null $clientId
     * @param array|string|int|null $clientSecret
     * @param array|string|int|null $grantType
     * @return void
     */
    public function getConnectorAuthentication(
        $url = null,
        $clientId = null,
        $clientSecret = null,
        $grantType = null
    ) {
        $url = $url ?: $this->authenticationApiUrl();
        $clientId = $clientId ?: $this->getClientId();
        $clientSecret = $clientSecret ?: $this->getClientSecret();
        $grantType = $grantType ?: $this->getGrantType();
        // Prepare the data
        $postData = [
            'grant_type'    => $grantType,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ];
        // Set headers
        $this->curl->setHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
        $this->curl->setOption(CURLOPT_TIMEOUT, 10);

        // Send the request
        $this->curl->post($url, http_build_query($postData));

        $response = json_decode($this->curl->getBody(), true);

        if (isset($response['access_token'])) {
            $this->setToken($response['access_token'], $response['expires_in']);
            $this->logger->addSuccessLog(json_encode($response));
            return $response['access_token'];
        } elseif (isset($response['error'])) {
            $this->logger->addErrorLog($response);
            return [
                'error' => $response['error']
            ];
        } else {
            $this->logger->addErrorLog($response);
            return [
                'error' => 'Unknown error from authentication response.'
            ];
        }
    }

    /**
     * Get a valid access token.
     * If $force=true, deletes the existing token and fetches a brand-new one.
     * Use $force=true after receiving a 401 to transparently refresh expired tokens.
     *
     * @param bool $force Force token refresh even if a token exists
     * @return string
     */
    public function getOrRefreshToken(bool $force = false): string
    {
        if (!$force) {
            $token = $this->getToken();
            if (!empty($token)) {
                return $token;
            }
        } else {
            // Clear the stale/expired token first
            try {
                $this->deleteToken();
            } catch (\Exception $e) {
                $this->logger->error('Failed to delete old token: ' . $e->getMessage());
            }
        }

        $newToken = $this->getConnectorAuthentication();
        return is_string($newToken) ? $newToken : '';
    }

    /**
     * Set auth token to cache
     *
     * @param string $token
     * @param int $duration (in seconds)
     */
    public function setToken($token, $duration = 3600)
    {
        $cacheKey = self::COOKIE_NAME; // Keep naming consistency for key
        $this->cache->save($token, $cacheKey, [self::CACHE_TAG], (int)$duration);
    }

    /**
     * Delete token from cache
     */
    public function deleteToken()
    {
        $this->cache->remove(self::COOKIE_NAME);
    }

    /**
     * Get auth token from cache
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->cache->load(self::COOKIE_NAME) ?: null;
    }
   
    /**
     * Get Contact Id
     *
     * @param [type] $customerData
     * @return void
     */
    public function getContactId($customerData)
    {
        $responseContactDetails = $this->fetchContactDetails($customerData);
        $customerId = '';
        if (!empty($responseContactDetails["Result"]["id"])) {
            $customerId = $responseContactDetails['Result']['id'];
        }
        return $customerId;
    }

    /**
     * Sync contact/user details with WhatsTalk before sending template messages.
     */
    public function syncWhatsTalkUser(array $userDetail, string $requestType = 'contact_sync', $customerId = null): array
    {
        if (empty($userDetail['mobileNumber']) || empty($userDetail['countryCode'])) {
            return ['success' => false, 'message' => 'Mobile number or country code missing'];
        }

        try {
            // Map userDetail keys to snake_case for the API
            $payload = [
                'first_name'   => (string)($userDetail['firstName'] ?? ''),
                'last_name'    => (string)($userDetail['lastName'] ?? ''),
                'country_code' => preg_replace('/\D/', '', (string)($userDetail['countryCode'] ?? '91')),
                'phone_number' => preg_replace('/\D/', '', (string)($userDetail['mobileNumber'] ?? '')),
            ];

            $this->eventLogger->logPayload($requestType, $payload, [
                'api_url' => $this->contactApiUrl(),
                'stage' => 'before_contact_sync',
            ]);

            $response = $this->fetchContactDetails($payload);
            
            $this->eventLogger->logApiResponse($requestType, $response, [
                'api_url' => $this->contactApiUrl(),
            ]);

            $message = (string)($response['Message'] ?? ($response['message'] ?? ''));
            $isAlreadyExists = strpos($message, 'already exists') !== false;
            $contactId = (string)($response['Result']['id'] ?? ($response['result']['id'] ?? ''));

            // If API says already exists but doesn't return id, try lookup via GET.
            if ($contactId === '' && $isAlreadyExists) {
                $contactId = $this->lookupContactId(
                    (string)$payload['country_code'],
                    (string)$payload['phone_number']
                );
            }

            $success = ($contactId !== '') || $isAlreadyExists;

            if ($success && $customerId) {
                $this->updateCustomerSyncStatus((int)$customerId, $contactId);
            }

            if ($success && $contactId !== '') {
                $this->setCachedContactId((string)$payload['country_code'], (string)$payload['phone_number'], $contactId);
            }

            return [
                'success' => $success,
                'message' => $isAlreadyExists ? 'Contact already exists' : ($message ?: 'Contact sync completed'),
                'contact_id' => $contactId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            $this->logger->error('WhatsTalk user sync failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Contact sync failed'];
        }
    }

    /**
     * Update customer sync status and last sync timestamp.
     *
     * @param int $customerId
     * @return void
     */
    private function updateCustomerSyncStatus(int $customerId, string $contactId = ''): void
    {
        try {
            // Senior Level: We must use a Model (Active Record) instead of a Data Object (Service Contract)
            // because Resource Model's saveAttribute expects an instance of \Magento\Framework\DataObject
            $customerModel = $this->customerFactory->create();
            $this->customerResource->load($customerModel, $customerId);
            
            if (!$customerModel->getId()) {
                throw new \Exception("Customer with ID {$customerId} not found.");
            }

            // Senior Level: Update only these specific attributes to skip heavy EAV validation and save loops
            $customerModel->setData('whatsapp_sync_status', 1);
            $customerModel->setData('whatsapp_last_sync', $this->dateTime->gmtDate());
            if ($contactId !== '') {
                $customerModel->setData('whatsapp_contact_id', $contactId);
            }
            
            $this->customerResource->saveAttribute($customerModel, 'whatsapp_sync_status');
            $this->customerResource->saveAttribute($customerModel, 'whatsapp_last_sync');
            if ($contactId !== '') {
                $this->customerResource->saveAttribute($customerModel, 'whatsapp_contact_id');
            }
            
            $this->logger->info("Successfully updated WhatsApp sync status for customer ID {$customerId}");
        } catch (\Exception $e) {
            $this->logger->error("Failed to update WhatsApp sync status for customer ID {$customerId}: " . $e->getMessage());
        }
    }

    /**
     * Convert placeholder array to API contract.
     *
     * @param array $placeholderValues
     * @return array
     */
    private function buildPlaceholderPayload(array $placeholderValues): array
    {
        $payload = [];

        foreach ($placeholderValues as $key => $value) {
            $payload[] = [
                'parameterName' => (string)$key,
                'parameterValue' => is_scalar($value) || $value === null
                    ? (string)$value
                    : json_encode($value),
            ];
        }

        return $payload;
    }

    /**
     * Get API Base URL (read from config, no trailing slash)
     *
     * @return string
     */
    public function baseUrl()
    {
        return rtrim((string) $this->getConfigValue(self::XML_PATH_BASE_URL), '/');
    }

    /**
     * Get Template API URL
     *
     * @return string
     */
    public function templateApiUrl()
    {
        return $this->baseUrl() . self::ENDPOINT_TEMPLATES;
    }

    /**
     * Get Contact API URL
     *
     * @return string
     */
    public function contactApiUrl()
    {
        return $this->messageBaseUrl() . self::ENDPOINT_CONTACT;
    }

    /**
     * Get Message API URL
     *
     * @return string
     */
    public function messageApiUrl()
    {
        return $this->messageBaseUrl() . self::ENDPOINT_MESSAGE;
    }

    /**
     * Get Message API Base URL (read from config, no trailing slash)
     *
     * @return string
     */
    public function messageBaseUrl()
    {
        $messageBaseUrl = (string) $this->getConfigValue(self::XML_PATH_MESSAGE_BASE_URL);
        if (empty($messageBaseUrl)) {
            return $this->baseUrl();
        }
        return rtrim($messageBaseUrl, '/');
    }

    /**
     * Get Language API URL
     *
     * @return string
     */
    public function languageApiUrl()
    {
        return $this->baseUrl() . self::ENDPOINT_LANGUAGE;
    }

    /**
     * Get Authentication API URL
     *
     * @return string
     */
    public function authenticationApiUrl()
    {
        return $this->getConfigValue(self::XML_PATH_AUTHENTICATION_API_URL);
    }

    /**
     * Get Client ID
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->getConfigValue(self::XML_PATH_CLIENT_ID);
    }

    /**
     * Get Client Secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->getConfigValue(self::XML_PATH_CLIENT_SECRET_KEY);
    }

    /**
     * Get Grant Type
     *
     * @return string
     */
    public function getGrantType()
    {
        return $this->getConfigValue(self::XML_PATH_GRANT_TYPE);
    }

    /**
     * Fetch all supported languages from Node.js API
     */
    public function getLanguages(): array
    {
        $url = $this->languageApiUrl() . '?page=0&size=100';
        $response = $this->callApi($url, 'GET', null, 'getLanguages');
        
        return $response['result']['data'] ?? [];
    }

    /**
     * Get the HTTP status of the last request
     */
    public function getCurlStatus(): int
    {
        return (int)$this->curl->getStatus();
    }

    /**
     * Elite API Caller with Automatic 401 Retry & Refresh
     */
    public function callApi(
        string $url,
        string $method = 'GET',
        ?array $payload = null,
        string $logContext = 'api_call'
    ): array {
        $token = $this->getOrRefreshToken();
        $attempt = 1;

        while ($attempt <= 2) {
            $headers = [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ];

            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 5);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, strtoupper($method));

            try {
                if ($payload !== null) {
                    $jsonPayload = json_encode($payload);
                if ($this->isDebugLoggingEnabled()) {
                    $this->logCurlCommand($url, $method, $headers, $jsonPayload);
                }
                if ($method === 'POST') {
                    $this->curl->post($url, $jsonPayload);
                } else {
                    $this->curl->setOption(CURLOPT_POSTFIELDS, $jsonPayload);
                    $this->curl->get($url); // Curl::get triggers the request
                }
            } else {
                if ($this->isDebugLoggingEnabled()) {
                    $this->logCurlCommand($url, $method, $headers);
                }
                $this->curl->get($url);
            }
            } catch (\Exception $e) {
                $this->logger->error('WhatsApp API call failed: ' . $e->getMessage(), [
                    'url' => $url,
                    'method' => $method,
                    'log_context' => $logContext,
                    'attempt' => $attempt
                ]);
                return ['success' => false, 'message' => $e->getMessage()];
            }

            $status = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();
            $response = json_decode($responseBody ?: '', true) ?: [];

            // Senior Level: Automatic Self-Healing on 401
            if ($status === 401 && $attempt === 1) {
                $this->logger->info("WhatsApp API [401] detected. Forcing token refresh and retrying...");
                $token = $this->getOrRefreshToken(true);
                $attempt++;
                continue;
            }

            // High Precision Logging
            if ($this->isDebugLoggingEnabled()) {
                $this->logger->loggedAsInfoData(
                    $url,
                    $logContext,
                    $response['Message'] ?? ($response['message'] ?? "Request $attempt completed"),
                    $headers,
                    $payload ?: [],
                    $response
                );
            }

            return $response;
        }

        return [];
    }

    /**
     * Get configuration value by path
     *
     * @param string $config_path
     * @return mixed
     */
    public function getConfigValue($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }

    /**
     * Log equivalent CURL command for debugging reliably handling JSON.
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param string|null $payload
     * @return void
     */
    private function logCurlCommand($url, $method, $headers, $payload = null)
    {
        $command = "curl --location --request $method '$url'";
        foreach ($headers as $key => $value) {
            $command .= " \\\n--header '$key: $value'";
        }
        if ($payload) {
            // High Security / Senior Level escape of single quotes for valid bash rendering
            $escapedPayload = str_replace("'", "'\\''", $payload);
            $command .= " \\\n--data '" . $escapedPayload . "'";
        }
        $this->logger->info("Equivalent CURL command:\n" . $command);
    }

    private function isDebugLoggingEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_LOGGING,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    private function resolveConversationId(array $userDetail, string $requestType, bool $syncContact): string
    {
        $contactId = (string)($userDetail['contactId'] ?? ($userDetail['contact_id'] ?? ''));
        if ($contactId !== '') {
            return $contactId;
        }

        $countryCode = preg_replace('/\D/', '', (string)($userDetail['countryCode'] ?? ''));
        $phoneNumber = preg_replace('/\D/', '', (string)($userDetail['mobileNumber'] ?? ''));
        // Guard: avoid API calls when phone is clearly invalid.
        if ($countryCode === '' || $phoneNumber === '' || strlen($phoneNumber) < 6) {
            return '';
        }

        $cached = $this->getCachedContactId($countryCode, $phoneNumber);
        if ($cached !== '') {
            return $cached;
        }

        if ($syncContact) {
            $sync = $this->syncWhatsTalkUser([
                'firstName' => (string)($userDetail['firstName'] ?? ''),
                'lastName' => (string)($userDetail['lastName'] ?? ''),
                'countryCode' => $countryCode,
                'mobileNumber' => $phoneNumber,
            ], $requestType . '_contact_sync');

            $syncedId = (string)($sync['contact_id'] ?? '');
            if ($syncedId !== '') {
                return $syncedId;
            }
        }

        // Fallback: lookup via GET (covers "already exists" without id).
        $lookedUp = $this->lookupContactId($countryCode, $phoneNumber);
        if ($lookedUp !== '') {
            $this->setCachedContactId($countryCode, $phoneNumber, $lookedUp);
            return $lookedUp;
        }

        // If sync is disabled for this event, we still need a conversation_id to send a message.
        // As a last resort, do a single sync attempt to create/update the contact and obtain an id.
        if (!$syncContact) {
            $sync = $this->syncWhatsTalkUser([
                'firstName' => (string)($userDetail['firstName'] ?? ''),
                'lastName' => (string)($userDetail['lastName'] ?? ''),
                'countryCode' => $countryCode,
                'mobileNumber' => $phoneNumber,
            ], $requestType . '_contact_sync_fallback');

            $syncedId = (string)($sync['contact_id'] ?? '');
            if ($syncedId !== '') {
                $this->setCachedContactId($countryCode, $phoneNumber, $syncedId);
                return $syncedId;
            }
        }

        return '';
    }

    private function lookupContactId(string $countryCode, string $phoneNumber): string
    {
        $countryCode = preg_replace('/\D/', '', $countryCode);
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        if ($countryCode === '' || $phoneNumber === '') {
            return '';
        }

        $base = $this->contactApiUrl();
        $queries = [
            ['country_code' => $countryCode, 'phone_number' => $phoneNumber],
            ['countryCode' => $countryCode, 'phoneNumber' => $phoneNumber],
            ['phone_number' => $phoneNumber],
            ['phoneNumber' => $phoneNumber],
        ];

        foreach ($queries as $query) {
            // Some environments return paginated lists even when filters are present.
            // Start with a reasonably large page size and expand page scan only if needed.
            $pageSize = 200;
            $maxPages = 3;

            for ($page = 0; $page < $maxPages; $page++) {
                $url = $base . '?' . http_build_query(array_merge($query, [
                    // Prefer API's canonical pagination params.
                    'page' => $page,
                    'size' => $pageSize,
                ]));

                $resp = $this->callApi($url, 'GET', null, 'lookupContactId');
                $id = $this->extractContactIdFromResponse($resp, $countryCode, $phoneNumber);
                if ($id !== '') {
                    return $id;
                }

                $totalPages = (int)(
                    $resp['result']['total_pages']
                    ?? $resp['Result']['total_pages']
                    ?? $resp['result']['totalPages']
                    ?? $resp['Result']['totalPages']
                    ?? 0
                );

                // If backend ignored filters (huge dataset), scan a bit deeper but still keep bounded.
                if ($page === 0 && $totalPages > $maxPages) {
                    $maxPages = min($totalPages, 10);
                }

                if ($totalPages > 0 && $page >= ($totalPages - 1)) {
                    break;
                }
            }
        }

        return '';
    }

    private function extractContactIdFromResponse(array $resp, string $countryCode, string $phoneNumber): string
    {
        $direct = (string)(
            $resp['Result']['id']
            ?? $resp['result']['id']
            ?? ''
        );
        if ($direct !== '') {
            return $direct;
        }

        $data = $resp['result']['data'] ?? $resp['Result']['data'] ?? $resp['result'] ?? $resp['Result'] ?? null;
        if (!is_array($data)) {
            return '';
        }

        // If response is a paginated list, select the exact match instead of the first record.
        $flatList = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $flatList = $data['data'];
        }

        foreach ($flatList as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowCountry = preg_replace('/\D/', '', (string)($row['country_code'] ?? $row['countryCode'] ?? ''));
            $rowPhone = preg_replace('/\D/', '', (string)($row['phone_number'] ?? $row['phoneNumber'] ?? ''));
            $rowId = (string)($row['id'] ?? '');

            if ($rowId !== '' && $rowCountry === $countryCode && $rowPhone === $phoneNumber) {
                return $rowId;
            }
        }

        return '';
    }

    private function getCachedContactId(string $countryCode, string $phoneNumber): string
    {
        $key = self::CONTACT_ID_CACHE_PREFIX . $countryCode . '_' . $phoneNumber;
        $value = $this->cache->load($key);
        return is_string($value) ? $value : '';
    }

    private function setCachedContactId(string $countryCode, string $phoneNumber, string $contactId): void
    {
        if ($contactId === '') {
            return;
        }

        $key = self::CONTACT_ID_CACHE_PREFIX . $countryCode . '_' . $phoneNumber;
        $this->cache->save($contactId, $key, [self::CACHE_TAG], self::CACHE_LIFETIME);
    }
}
