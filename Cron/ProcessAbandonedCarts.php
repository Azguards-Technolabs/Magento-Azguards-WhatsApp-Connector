<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppNotificationService;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

class ProcessAbandonedCarts
{
    private const XML_PATH_ENABLE = 'whatsApp_conector/abandon_cart/enable';
    private const XML_PATH_ABANDON_AFTER_MINUTES = 'whatsApp_conector/abandon_cart/abandon_after_minutes';
    private const XML_PATH_MAX_PER_RUN = 'whatsApp_conector/abandon_cart/max_per_run';
    private const LOCK_NAME = 'azguards_whatsapp_abandoned_cart_cron';
    private const LOCK_TIMEOUT = 0;

    /**
     * @var QuoteCollectionFactory
     */
    private QuoteCollectionFactory $quoteCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var WhatsAppNotificationService
     */
    private WhatsAppNotificationService $notificationService;

    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var LockManagerInterface
     */
    private LockManagerInterface $lockManager;

    /**
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param WhatsAppNotificationService $notificationService
     * @param ApiHelper $apiHelper
     * @param DateTime $dateTime
     * @param Logger $logger
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        QuoteCollectionFactory $quoteCollectionFactory,
        ResourceConnection $resourceConnection,
        WhatsAppNotificationService $notificationService,
        ApiHelper $apiHelper,
        DateTime $dateTime,
        Logger $logger,
        LockManagerInterface $lockManager
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->notificationService = $notificationService;
        $this->apiHelper = $apiHelper;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->lockManager = $lockManager;
    }

    /**
     * Process abandoned carts and dispatch notifications.
     *
     * @return void
     */
    public function execute(): void
    {
        $lockAcquired = false;

        try {
            $lockAcquired = $this->lockManager->lock(self::LOCK_NAME, self::LOCK_TIMEOUT);
            if (!$lockAcquired) {
                $this->logger->warning('Abandoned cart cron skipped because another execution is already running.');
                return;
            }

            if (!(bool)$this->apiHelper->getConfigValue(self::XML_PATH_ENABLE)) {
                return;
            }

            $afterMinutes = (int)$this->apiHelper->getConfigValue(self::XML_PATH_ABANDON_AFTER_MINUTES);
            if ($afterMinutes <= 0) {
                $afterMinutes = 60;
            }

            $maxPerRun = (int)$this->apiHelper->getConfigValue(self::XML_PATH_MAX_PER_RUN);
            if ($maxPerRun <= 0) {
                $maxPerRun = 50;
            }

            $cutoffTs = time() - ($afterMinutes * 60);
            $cutoff = gmdate('Y-m-d H:i:s', $cutoffTs);

            $this->logger->info(sprintf(
                'Abandoned cart cron start. cutoff=%s max_per_run=%d',
                $cutoff,
                $maxPerRun
            ));

            $collection = $this->quoteCollectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            $collection->addFieldToFilter('items_count', ['gt' => 0]);
            $collection->addFieldToFilter('updated_at', ['lteq' => $cutoff]);
            $collection->setOrder('updated_at', 'ASC');
            $collection->setPageSize($maxPerRun);

            $processed = 0;
            foreach ($collection as $quote) {
                $quoteId = (int)$quote->getId();
                if ($quoteId <= 0) {
                    continue;
                }

                if ($this->isAlreadyNotified($quoteId)) {
                    continue;
                }

                $processed++;
                $templateId = '';
                try {
                    $this->logger->info(sprintf(
                        'Abandoned cart candidate. quote_id=%d store_id=%s customer_id=%s '
                        . 'email=%s items=%s updated_at=%s',
                        $quoteId,
                        (string)$quote->getStoreId(),
                        (string)$quote->getCustomerId(),
                        (string)$quote->getCustomerEmail(),
                        (string)$quote->getItemsCount(),
                        (string)$quote->getUpdatedAt()
                    ));

                    $response = $this->notificationService->notifyAbandonedCart($quote);
                    $templateId = (string)($response['template_id'] ?? '');

                    if (!empty($response['success'])) {
                        $this->markNotified(
                            $quoteId,
                            (int)$quote->getStoreId(),
                            (string)$quote->getCustomerEmail(),
                            $templateId,
                            'sent',
                            null
                        );
                    } else {
                        $this->markNotified(
                            $quoteId,
                            (int)$quote->getStoreId(),
                            (string)$quote->getCustomerEmail(),
                            $templateId,
                            'failed',
                            (string)($response['message'] ?? 'Unknown')
                        );
                    }
                } catch (\Throwable $e) {
                    $this->logger->error(sprintf(
                        'Abandoned cart notify failed. quote_id=%d error=%s',
                        $quoteId,
                        $e->getMessage()
                    ));
                    $this->markNotified(
                        $quoteId,
                        (int)$quote->getStoreId(),
                        (string)$quote->getCustomerEmail(),
                        $templateId,
                        'failed',
                        $e->getMessage()
                    );
                }
            }

            $this->logger->info(sprintf(
                'Abandoned cart cron end. scanned=%d processed=%d',
                (int)$collection->getSize(),
                $processed
            ));
        } finally {
            if ($lockAcquired) {
                $this->lockManager->unlock(self::LOCK_NAME);
            }
        }
    }

    /**
     * Check whether the quote was already notified.
     *
     * @param int $quoteId
     * @return bool
     */
    private function isAlreadyNotified(int $quoteId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('azguards_whatsapp_abandoned_cart_notify');

        $select = $connection->select()
            ->from($table, ['quote_id'])
            ->where('quote_id = ?', $quoteId)
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * Persist abandoned-cart notification state.
     *
     * @param int $quoteId
     * @param int $storeId
     * @param string $customerEmail
     * @param string $templateId
     * @param string $status
     * @param string|null $errorMessage
     * @return void
     */
    private function markNotified(
        int $quoteId,
        int $storeId,
        string $customerEmail,
        string $templateId,
        string $status,
        ?string $errorMessage
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('azguards_whatsapp_abandoned_cart_notify');

        $connection->insertOnDuplicate($table, [
            'quote_id' => $quoteId,
            'store_id' => $storeId ?: null,
            'customer_email' => $customerEmail ?: null,
            'template_id' => $templateId ?: null,
            'status' => $status,
            'error_message' => $errorMessage,
            'notified_at' => $this->dateTime->gmtDate(),
        ], ['status', 'error_message', 'template_id', 'notified_at']);
    }
}
