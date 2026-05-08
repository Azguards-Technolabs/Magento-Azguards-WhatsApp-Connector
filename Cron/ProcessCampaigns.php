<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Model\Service\CampaignSchedulerService;
use Azguards\WhatsAppConnect\Model\Service\CampaignWorkerService;
use Psr\Log\LoggerInterface;

/**
 * Cron job for processing WhatsApp marketing campaigns.
 */
class ProcessCampaigns
{
    /**
     * @var CampaignSchedulerService
     */
    private CampaignSchedulerService $campaignSchedulerService;

    /**
     * @var CampaignWorkerService
     */
    private CampaignWorkerService $campaignWorkerService;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CampaignSchedulerService $campaignSchedulerService
     * @param CampaignWorkerService $campaignWorkerService
     * @param LoggerInterface $logger
     */
    public function __construct(
        CampaignSchedulerService $campaignSchedulerService,
        CampaignWorkerService $campaignWorkerService,
        LoggerInterface $logger
    ) {
        $this->campaignSchedulerService = $campaignSchedulerService;
        $this->campaignWorkerService = $campaignWorkerService;
        $this->logger = $logger;
    }

    /**
     * Execute cron job.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('Campaign Cron: Starting WhatsApp campaign scheduler.');
        try {
            // First, identify new scheduled campaigns and populate the queue
            $this->campaignSchedulerService->execute('Cron');
            
            // Second, process any pending items in the queue (batch processing)
            $this->campaignWorkerService->execute('Cron');
            
            $this->logger->info('Campaign Cron: Completed WhatsApp campaign processing.');
        } catch (\Throwable $exception) {
            $msg = 'Campaign Cron: Failed WhatsApp campaign processing. Error: ' . $exception->getMessage();
            $this->logger->error($msg);
        }
    }
}
