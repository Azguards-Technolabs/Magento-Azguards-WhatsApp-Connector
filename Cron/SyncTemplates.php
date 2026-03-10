<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Model\Service\TemplateService;
use Psr\Log\LoggerInterface;

class SyncTemplates
{
    private $templateService;
    private $logger;

    public function __construct(
        TemplateService $templateService,
        LoggerInterface $logger
    ) {
        $this->templateService = $templateService;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $this->logger->info("Cron Job: Starting WhatsApp template sync.");
        try {
            $summary = $this->templateService->syncTemplates();
            $this->logger->info(sprintf(
                "Cron Job: WhatsApp template sync completed. Created: %d, Updated: %d, Errors: %d",
                $summary['created'],
                $summary['updated'],
                $summary['errors']
            ));
        } catch (\Exception $e) {
            $this->logger->error("Cron Job: WhatsApp template sync failed. Error: " . $e->getMessage());
        }
    }
}
