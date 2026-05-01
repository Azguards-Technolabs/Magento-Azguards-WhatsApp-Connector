<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Logger\SyncProcessLogger;
use Azguards\WhatsAppConnect\Model\Config\CronConfig;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Magento\Framework\Lock\LockManagerInterface;

class SyncTemplates
{
    private const LOCK_NAME = 'azguards_whatsapp_template_sync_cron';
    private const LOCK_TIMEOUT = 0;

    /**
     * @param TemplateService $templateService
     * @param SyncProcessLogger $logger
     * @param CronConfig $cronConfig
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly SyncProcessLogger $logger,
        private readonly CronConfig $cronConfig,
        private readonly LockManagerInterface $lockManager
    ) {
    }

    /**
     * Run the template sync cron job.
     *
     * @return void
     */
    public function execute(): void
    {
        $lockAcquired = false;

        try {
            $lockAcquired = $this->lockManager->lock(self::LOCK_NAME, self::LOCK_TIMEOUT);
            if (!$lockAcquired) {
                $this->logger->warning('Template sync cron skipped because another execution is already running.');
                return;
            }

            $this->logger->info('Template sync cron started.', [
                'schedule' => $this->cronConfig->getTemplateSyncSchedule()
            ]);

            $summary = $this->templateService->syncTemplates();

            $this->logger->info('Template sync cron completed.', [
                'schedule' => $this->cronConfig->getTemplateSyncSchedule(),
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Template sync cron failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        } finally {
            if ($lockAcquired) {
                $this->lockManager->unlock(self::LOCK_NAME);
            }
        }
    }
}
