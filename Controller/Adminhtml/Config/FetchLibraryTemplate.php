<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Azguards\WhatsAppConnect\Model\Service\MetaLibraryTemplateService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class FetchLibraryTemplate extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::config';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var MetaLibraryTemplateService
     */
    private $libraryTemplateService;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param MetaLibraryTemplateService $libraryTemplateService
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MetaLibraryTemplateService $libraryTemplateService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->libraryTemplateService = $libraryTemplateService;
    }

    /**
     * Fetch and map template from Meta Library
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $eventCode = (string)$this->getRequest()->getParam('event_code');
        $language = (string)$this->getRequest()->getParam('language', 'en_US');

        if (!$eventCode) {
            return $result->setData([
                'success' => false,
                'message' => __('Event code is required.')
            ]);
        }

        $mappedData = $this->libraryTemplateService->getMappedTemplate($eventCode, $language);

        return $result->setData($mappedData);
    }
}
