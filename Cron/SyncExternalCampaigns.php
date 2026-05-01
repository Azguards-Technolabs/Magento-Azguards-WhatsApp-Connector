<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Logger\SyncProcessLogger;
use Azguards\WhatsAppConnect\Model\Config\CronConfig;
use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Magento\Framework\Lock\LockManagerInterface;

class SyncExternalCampaigns
{
    private const LOCK_NAME = 'azguards_whatsapp_campaign_sync_cron';
    private const LOCK_TIMEOUT = 0;

    /**
     * @param CampaignService $campaignService
     * @param SyncProcessLogger $logger
     * @param CronConfig $cronConfig
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        private readonly CampaignService $campaignService,
        private readonly SyncProcessLogger $logger,
        private readonly CronConfig $cronConfig,
        private readonly LockManagerInterface $lockManager
    ) {
    }

    /**
     * Run the external campaign sync cron job.
     *
     * @return void
     */
    public function execute(): void
    {
        $lockAcquired = false;

        try {
            $lockAcquired = $this->lockManager->lock(self::LOCK_NAME, self::LOCK_TIMEOUT);
            if (!$lockAcquired) {
                $this->logger->warning('Campaign sync cron skipped because another execution is already running.');
                return;
            }

            $this->logger->info('Campaign sync cron started.', [
                'schedule' => $this->cronConfig->getCampaignSyncSchedule()
            ]);

            $result = $this->campaignService->syncExternalAll();

            $this->logger->info('Campaign sync cron completed.', [
                'schedule' => $this->cronConfig->getCampaignSyncSchedule(),
                'result' => $result
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Campaign sync cron failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        } finally {
            if ($lockAcquired) {
                $this->lockManager->unlock(self::LOCK_NAME);
            }
        }
    }
}
