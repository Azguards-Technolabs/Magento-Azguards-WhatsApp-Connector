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
use Azguards\WhatsAppConnect\Logger\Logger;
use Magento\Framework\App\CacheInterface;

class ApiHelper extends AbstractHelper
{
    // Config paths
    public const XML_PATH_BASE_URL            = 'whatsApp_conector/general/base_url';
    public const XML_PATH_AUTHENTICATION_API_URL = 'whatsApp_conector/general/authentication_api_url';
    public const XML_PATH_CLIENT_ID           = 'whatsApp_conector/general/client_id';
    public const XML_PATH_CLIENT_SECRET_KEY   = 'whatsApp_conector/general/client_secret_key';
    public const XML_PATH_GRANT_TYPE          = 'whatsApp_conector/general/grant_type';

    // API endpoint paths (appended to base_url, which must include version prefix e.g. /meta-service/v1)
    public const ENDPOINT_TEMPLATES = '/template';
    public const ENDPOINT_CONTACT   = '/contact';
    public const ENDPOINT_MESSAGE   = '/message/sendTemplate';
    public const ENDPOINT_LANGUAGE  = '/language';
    // public const COOKIE_NAME = 'whatsApp-conector';
    public const COOKIE_NAME = 'wa_auth_token';
    public const CACHE_TAG = 'whatsapp_templates';
    public const CACHE_LIFETIME = 86400; // 24 hours

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
        ScopeConfigInterface $scopeConfig,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
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

            $this->logger->loggedAsInfoData(
                $url,
                'fetchTemplates',
                $response['Message'] ?? 'Success',
                $headers,
                [],
                $response
            );

            $this->_templatesCache[$limit] = $response;
            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch contact details from API
     *
     * @param array $data
     * @return array
     */
    public function fetchContactDetails($data)
    {
        $url = $this->contactApiUrl();
        $accessToken = $this->getOrRefreshToken();

        $doCall = function ($token) use ($url, $data) {
            $this->curl->setHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]);
            $this->curl->setOption(CURLOPT_TIMEOUT, 10);
            $this->curl->post($url, $data);
        };

        $doCall($accessToken);

        // Auto-retry on 401
        if ($this->curl->getStatus() === 401) {
            $accessToken = $this->getOrRefreshToken(true);
            $doCall($accessToken);
        }

        return json_decode($this->curl->getBody(), true);
    }

    /**
     * Get country calling codes by country code
     *
     * @param string $countrycode
     * @return string
     */
    public function getCountryCallingCodes($countrycode)
    {

        $countryCallingCodes = [
            'AD'=>'376',
            'AE'=>'971',
            'AF'=>'93',
            'AG'=>'1268',
            'AI'=>'1264',
            'AL'=>'355',
            'AM'=>'374',
            'AN'=>'599',
            'AO'=>'244',
            'AQ'=>'672',
            'AR'=>'54',
            'AS'=>'1684',
            'AT'=>'43',
            'AU'=>'61',
            'AW'=>'297',
            'AZ'=>'994',
            'BA'=>'387',
            'BB'=>'1246',
            'BD'=>'880',
            'BE'=>'32',
            'BF'=>'226',
            'BG'=>'359',
            'BH'=>'973',
            'BI'=>'257',
            'BJ'=>'229',
            'BL'=>'590',
            'BM'=>'1441',
            'BN'=>'673',
            'BO'=>'591',
            'BR'=>'55',
            'BS'=>'1242',
            'BT'=>'975',
            'BW'=>'267',
            'BY'=>'375',
            'BZ'=>'501',
            'CA'=>'1',
            'CC'=>'61',
            'CD'=>'243',
            'CF'=>'236',
            'CG'=>'242',
            'CH'=>'41',
            'CI'=>'225',
            'CK'=>'682',
            'CL'=>'56',
            'CM'=>'237',
            'CN'=>'86',
            'CO'=>'57',
            'CR'=>'506',
            'CU'=>'53',
            'CV'=>'238',
            'CX'=>'61',
            'CY'=>'357',
            'CZ'=>'420',
            'DE'=>'49',
            'DJ'=>'253',
            'DK'=>'45',
            'DM'=>'1767',
            'DO'=>'1809',
            'DZ'=>'213',
            'EC'=>'593',
            'EE'=>'372',
            'EG'=>'20',
            'ER'=>'291',
            'ES'=>'34',
            'ET'=>'251',
            'FI'=>'358',
            'FJ'=>'679',
            'FK'=>'500',
            'FM'=>'691',
            'FO'=>'298',
            'FR'=>'33',
            'GA'=>'241',
            'GB'=>'44',
            'GD'=>'1473',
            'GE'=>'995',
            'GH'=>'233',
            'GI'=>'350',
            'GL'=>'299',
            'GM'=>'220',
            'GN'=>'224',
            'GQ'=>'240',
            'GR'=>'30',
            'GT'=>'502',
            'GU'=>'1671',
            'GW'=>'245',
            'GY'=>'592',
            'HK'=>'852',
            'HN'=>'504',
            'HR'=>'385',
            'HT'=>'509',
            'HU'=>'36',
            'ID'=>'62',
            'IE'=>'353',
            'IL'=>'972',
            'IM'=>'44',
            'IN'=>'91',
            'IQ'=>'964',
            'IR'=>'98',
            'IS'=>'354',
            'IT'=>'39',
            'JM'=>'1876',
            'JO'=>'962',
            'JP'=>'81',
            'KE'=>'254',
            'KG'=>'996',
            'KH'=>'855',
            'KI'=>'686',
            'KM'=>'269',
            'KN'=>'1869',
            'KP'=>'850',
            'KR'=>'82',
            'KW'=>'965',
            'KY'=>'1345',
            'KZ'=>'7',
            'LA'=>'856',
            'LB'=>'961',
            'LC'=>'1758',
            'LI'=>'423',
            'LK'=>'94',
            'LR'=>'231',
            'LS'=>'266',
            'LT'=>'370',
            'LU'=>'352',
            'LV'=>'371',
            'LY'=>'218',
            'MA'=>'212',
            'MC'=>'377',
            'MD'=>'373',
            'ME'=>'382',
            'MF'=>'1599',
            'MG'=>'261',
            'MH'=>'692',
            'MK'=>'389',
            'ML'=>'223',
            'MM'=>'95',
            'MN'=>'976',
            'MO'=>'853',
            'MP'=>'1670',
            'MR'=>'222',
            'MS'=>'1664',
            'MT'=>'356',
            'MU'=>'230',
            'MV'=>'960',
            'MW'=>'265',
            'MX'=>'52',
            'MY'=>'60',
            'MZ'=>'258',
            'NA'=>'264',
            'NC'=>'687',
            'NE'=>'227',
            'NG'=>'234',
            'NI'=>'505',
            'NL'=>'31',
            'NO'=>'47',
            'NP'=>'977',
            'NR'=>'674',
            'NU'=>'683',
            'NZ'=>'64',
            'OM'=>'968',
            'PA'=>'507',
            'PE'=>'51',
            'PF'=>'689',
            'PG'=>'675',
            'PH'=>'63',
            'PK'=>'92',
            'PL'=>'48',
            'PM'=>'508',
            'PN'=>'870',
            'PR'=>'1',
            'PT'=>'351',
            'PW'=>'680',
            'PY'=>'595',
            'QA'=>'974',
            'RO'=>'40',
            'RS'=>'381',
            'RU'=>'7',
            'RW'=>'250',
            'SA'=>'966',
            'SB'=>'677',
            'SC'=>'248',
            'SD'=>'249',
            'SE'=>'46',
            'SG'=>'65',
            'SH'=>'290',
            'SI'=>'386',
            'SK'=>'421',
            'SL'=>'232',
            'SM'=>'378',
            'SN'=>'221',
            'SO'=>'252',
            'SR'=>'597',
            'ST'=>'239',
            'SV'=>'503',
            'SY'=>'963',
            'SZ'=>'268',
            'TC'=>'1649',
            'TD'=>'235',
            'TG'=>'228',
            'TH'=>'66',
            'TJ'=>'992',
            'TK'=>'690',
            'TL'=>'670',
            'TM'=>'993',
            'TN'=>'216',
            'TO'=>'676',
            'TR'=>'90',
            'TT'=>'1868',
            'TV'=>'688',
            'TW'=>'886',
            'TZ'=>'255',
            'UA'=>'380',
            'UG'=>'256',
            'US'=>'1',
            'UY'=>'598',
            'UZ'=>'998',
            'VA'=>'39',
            'VC'=>'1784',
            'VE'=>'58',
            'VG'=>'1284',
            'VI'=>'1340',
            'VN'=>'84',
            'VU'=>'678',
            'WF'=>'681',
            'WS'=>'685',
            'XK'=>'381',
            'YE'=>'967',
            'YT'=>'262',
            'ZA'=>'27',
            'ZM'=>'260',
            'ZW'=>'263'
        ];
        return isset($countryCallingCodes[$countrycode]) ?
        $countryCallingCodes[$countrycode] : '00'; // Default if not found
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
        $templates = $this->getStatiTemplate();
        $templateVerible = [];
        foreach ($templates as $item) {
            if ($item['id'] === $templateId) {
                if (!empty($item['templateBodyTextSampleValues'])) {
                    foreach ($item['templateBodyTextSampleValues'] as $param) {
                        $type = $param['parameterName'];
                        $identifierPrefix = 'catalogsearch_fulltext_';
                        $templateVerible[] = [
                            'title' => $param['parameterName'] ?? '',
                            'identifier' => $identifierPrefix . $type,
                            'order' => $param['parameterName'] ?? '',
                            'type' => $type,
                        ];
                    }
                }
            }
        }
        return $templateVerible;
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
        $url = $this->messageApiUrl();
        if (empty($templateId)) {
            return ['success' => false, 'message' => 'Template ID are required'];
        }

        $convertedPlaceholderValues = [];
        foreach ($tempaletVerible as $key => $value) {
            $convertedPlaceholderValues[] = [
                'parameterName'  => $key,
                'parameterValue' => $value
            ];
        }
        $payload = [
            'templateId'        => $templateId,
            'userDetail'        => $userDetail,
            'placeholderValues' => $convertedPlaceholderValues,
        ];

        $accessToken = $this->getOrRefreshToken();

        $doCall = function ($token) use ($url, $payload) {
            $headers = [
                'Content-Type'  => 'application/json',
                'businessId'    => '18462116-8abf-4960-80b2-dd6c76e2532c',
                'userId'        => 'a008d8b8-bc54-4e43-9a62-67b3c1b546f3',
                'Authorization' => 'Bearer ' . $token,
            ];
            $this->curl->setOption(CURLOPT_TIMEOUT, 10);
            $this->curl->setHeaders($headers);
            $this->curl->post($url, json_encode($payload));
            return $headers;
        };

        try {
            $headers = $doCall($accessToken);

            // Auto-retry on 401
            if ($this->curl->getStatus() === 401) {
                $accessToken = $this->getOrRefreshToken(true);
                $headers = $doCall($accessToken);
            }

            $response = json_decode($this->curl->getBody(), true);
            $this->logger->info('Logger working...');

            if (isset($response['Result']['status']) && $response['Result']['status'] === 'success') {
                $this->logger->loggedAsInfoData($url, $requestType, $response['message'] ?? '', $headers, $payload, $response);
                return ['success' => true, 'message' => $response['Result']['message']];
            } else {
                $this->logger->loggedAsInfoData($url, $requestType, $response['message'] ?? '', $headers, $payload, $response);
                return ['success' => false, 'message' => $response['Message'] ?? 'Failed to send message'];
            }
        } catch (\Exception $e) {
            $this->logger->info('WhatsApp API Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'API request failed'];
        }
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
        $userDetail = [
            'firstName'     => $billingAddress ? $billingAddress->getFirstname() : '',
            'lastName'      => $billingAddress ? $billingAddress->getLastname() : '',
            'countryCode'   => $countryCode,
            'mobileNumber'  => $billingAddress ? $billingAddress->getTelephone() : '',
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
     * Set auth token to cookie
     *
     * @param string $token
     * @param int $duration (in seconds)
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException
     */
    public function setToken($token, $duration = 3600)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $token, $metadata);
    }

    /**
     * Delete token cookie
     *
     * @throws FailureToSendException
     * @throws InputException
     */
    public function deleteToken()
    {
        $this->cookieManager->deleteCookie(
            self::COOKIE_NAME,
            $this->cookieMetadataFactory
                ->createCookieMetadata()
                ->setPath($this->sessionManager->getCookiePath())
                ->setDomain($this->sessionManager->getCookieDomain())
        );
    }

    /**
     * Get auth token from cookie
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->cookieManager->getCookie(self::COOKIE_NAME);
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
        return $this->baseUrl() . self::ENDPOINT_CONTACT;
    }

    /**
     * Get Message API URL
     *
     * @return string
     */
    public function messageApiUrl()
    {
        return $this->baseUrl() . self::ENDPOINT_MESSAGE;
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
     *
     * @return array
     */
    public function getLanguages()
    {
        try {
            $url = $this->languageApiUrl() . '?page=0&size=100';
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

            // Auto-retry on 401
            if ($this->curl->getStatus() === 401) {
                $accessToken = $this->getOrRefreshToken(true);
                $headers = $doCall($accessToken);
            }

            $response = json_decode($this->curl->getBody(), true);

            $this->logger->loggedAsInfoData(
                $url,
                'getLanguages',
                'Successfully fetched languages',
                $headers,
                [],
                $response
            );

            return $response['result']['data'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching languages: ' . $e->getMessage());
            return [];
        }
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
}
