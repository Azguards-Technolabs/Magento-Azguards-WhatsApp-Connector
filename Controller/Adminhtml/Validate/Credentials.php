<?php
namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Validate;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Framework\App\Request\InvalidRequestException;

class Credentials extends Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $apiHelper;

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

    public function execute()
	{
	    $result = $this->resultJsonFactory->create();
	    $clientId = $this->getRequest()->getParam('client_id');
		$clientSecret = $this->getRequest()->getParam('client_secret');
		$grantType = $this->getRequest()->getParam('grant_type');
		$authUrl = $this->getRequest()->getParam('auth_url');
	    $connectorAuthentication = $this->apiHelper->getConnectorAuthentication($authUrl, $clientId, $clientSecret, $grantType);
	   	if (empty($connectorAuthentication['error'])) {
		    $connectorAuthentication = $connectorAuthentication;
		    return $result->setData([
	            'success' => true,
	            'message' => 'Credentials are valid.'
	        ]);
		} elseif (isset($connectorAuthentication['error'])) {
			return $result->setData([
		        'success' => false,
		        'message' => $connectorAuthentication['error']
		    ]);
		} else {
		    return $result->setData([
		        'success' => false,
		        'message' => 'Unknown error from authentication response.'
		    ]);
		}
	}
}
