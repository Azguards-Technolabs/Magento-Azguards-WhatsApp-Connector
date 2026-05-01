<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Azguards\WhatsAppConnect\Model\Service\MediaResolver;
use Azguards\WhatsAppConnect\Model\Service\MediaDocumentService;
use Azguards\WhatsAppConnect\Model\Service\MediaPersistenceService;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller for resolving a media handler into a preview URL.
 * Used by the admin UI to show live previews when a handler is available.
 */
class ResolveMedia extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

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
     * Resolve media handler to preview link.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $handlerRaw = $this->getRequest()->getParam('handler');

        if (empty($handlerRaw)) {
            return $result->setData([
                'success' => false,
                'message' => __('Handler parameter is missing.')
            ]);
        }

        try {
            // Use senior-level MediaResolver to extract the actual document ID
            $documentId = $this->mediaResolver->resolveHandler($handlerRaw);

            if (!$documentId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Could not extract a valid document ID from the provided handler.')
                ]);
            }

            // Fetch the preview link from the Data Manager API (Fast mode)
            $previewUrl = $this->mediaDocumentService->getPreviewLink($documentId, false);

            if ($previewUrl) {
                // LAZY PERSISTENCE: Save locally for future use
                if (filter_var($previewUrl, FILTER_VALIDATE_URL)) {
                    $localPath = $this->mediaPersistence->persistFromUrl($previewUrl, $documentId . '_resolved');
                    if ($localPath) {
                        $previewUrl = rtrim(
                            $this->storeManager->getStore()->getBaseUrl(
                                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                            ),
                            '/'
                        ) . '/' . ltrim($localPath, '/');
                    }
                }

                return $result->setData([
                    'success' => true,
                    'preview_url' => $previewUrl,
                    'document_id' => $documentId
                ]);
            }

            return $result->setData([
                'success' => false,
                'message' => __('Could not resolve preview link for the provided handler.')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ResolveMedia Controller Error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while resolving the media handler.')
            ]);
        }
    }
}
