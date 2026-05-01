<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CronConfig
{
    public const XML_PATH_CAMPAIGN_SYNC_SCHEDULE = 'whatsApp_conector/cron/campaign_sync_schedule';
    public const XML_PATH_CONTACT_SYNC_SCHEDULE = 'whatsApp_conector/cron/contact_sync_schedule';
    public const XML_PATH_TEMPLATE_SYNC_SCHEDULE = 'whatsApp_conector/cron/template_sync_schedule';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Return the campaign sync cron schedule.
     *
     * @param string|null $scopeCode
     * @return string
     */
    public function getCampaignSyncSchedule(?string $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CAMPAIGN_SYNC_SCHEDULE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * Return the contact sync cron schedule.
     *
     * @param string|null $scopeCode
     * @return string
     */
    public function getContactSyncSchedule(?string $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CONTACT_SYNC_SCHEDULE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * Return the template sync cron schedule.
     *
     * @param string|null $scopeCode
     * @return string
     */
    public function getTemplateSyncSchedule(?string $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_TEMPLATE_SYNC_SCHEDULE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
}
