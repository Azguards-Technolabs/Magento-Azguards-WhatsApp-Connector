<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Azguards\WhatsAppConnect\Model\Service\MediaResolver;
use Azguards\WhatsAppConnect\Model\Service\MediaDocumentService;
use Azguards\WhatsAppConnect\Model\Service\MediaPersistenceService;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ResolveMedia extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::config';

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var MediaResolver
     */
    private MediaResolver $mediaResolver;

    /**
     * @var MediaDocumentService
     */
    private MediaDocumentService $mediaDocumentService;

    /**
     * @var MediaPersistenceService
     */
    private MediaPersistenceService $mediaPersistence;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param MediaResolver $mediaResolver
     * @param MediaDocumentService $mediaDocumentService
     * @param MediaPersistenceService $mediaPersistence
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MediaResolver $mediaResolver,
        MediaDocumentService $mediaDocumentService,
        MediaPersistenceService $mediaPersistence,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mediaResolver = $mediaResolver;
        $this->mediaDocumentService = $mediaDocumentService;
        $this->mediaPersistence = $mediaPersistence;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Resolve a stored media handler to a preview URL.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $handlerRaw = (string)$this->getRequest()->getParam('handler');
        if ($handlerRaw === '') {
            $handlerRaw = (string)$this->getRequest()->getParam('handle');
        }

        if ($handlerRaw === '') {
            return $result->setData([
                'success' => false,
                'message' => __('Handler parameter is missing.')
            ]);
        }

        try {
            $documentId = $this->mediaResolver->resolveHandler($handlerRaw);
            if (!$documentId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Could not extract a valid document ID from the provided handler.')
                ]);
            }

            $previewUrl = $this->mediaDocumentService->getPreviewLink($documentId, false);
            if ($previewUrl && filter_var($previewUrl, FILTER_VALIDATE_URL)) {
                $localPath = $this->mediaPersistence->persistFromUrl($previewUrl, $documentId . '_resolved');
                if ($localPath) {
                    $previewUrl = rtrim(
                        $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                        '/'
                    ) . '/' . ltrim($localPath, '/');
                }
            }

            if ($previewUrl) {
                return $result->setData([
                    'success' => true,
                    'url' => $previewUrl,
                    'document_id' => $documentId
                ]);
            }

            return $result->setData([
                'success' => false,
                'message' => __('Could not resolve preview link for the provided handler.')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Config ResolveMedia Error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while resolving the media handler.')
            ]);
        }
    }
}
