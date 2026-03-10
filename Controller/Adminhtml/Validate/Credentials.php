<?php
namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Validate;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;

/**
 * Class Credentials
 * Validates WhatsApp Connector API credentials via Admin AJAX
 */
class Credentials extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * Credentials constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ApiHelper $apiHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ApiHelper $apiHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->apiHelper = $apiHelper;
        parent::__construct($context);
    }

    /**
     * Execute method to validate credentials
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $clientId = $this->getRequest()->getParam('client_id');
        $clientSecret = $this->getRequest()->getParam('client_secret');
        $grantType = $this->getRequest()->getParam('grant_type');
        $authUrl = $this->getRequest()->getParam('auth_url');

        $authResponse = $this->apiHelper->getConnectorAuthentication(
            $authUrl,
            $clientId,
            $clientSecret,
            $grantType
        );

        if (empty($authResponse['error'])) {
            return $result->setData([
                'success' => true,
                'message' => __('Credentials are valid.')
            ]);
        } elseif (isset($authResponse['error'])) {
            return $result->setData([
                'success' => false,
                'message' => $authResponse['error']
            ]);
        }

        return $result->setData([
            'success' => false,
            'message' => __('Unknown error from authentication response.')
        ]);
    }
}
