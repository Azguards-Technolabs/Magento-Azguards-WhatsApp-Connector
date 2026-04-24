<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\CampaignFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign as CampaignResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class CampaignService
{
    private CampaignFactory $campaignFactory;
    private CampaignResource $campaignResource;
    private CollectionFactory $collectionFactory;
    private TimezoneInterface $timezone;
    private Logger $logger;
    private \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory $queueCollectionFactory;
    private \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue $queueResource;
    private WhatsAppEventLogger $eventLogger;

    private ExternalSchedulerService $externalSchedulerService;

    public function __construct(
        CampaignFactory $campaignFactory,
        CampaignResource $campaignResource,
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezone,
        Logger $logger,
        \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory $queueCollectionFactory,
        \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue $queueResource,
        WhatsAppEventLogger $eventLogger,
        ExternalSchedulerService $externalSchedulerService
    ) {
        $this->externalSchedulerService = $externalSchedulerService;
        $this->campaignFactory = $campaignFactory;
        $this->campaignResource = $campaignResource;
        $this->collectionFactory = $collectionFactory;
        $this->timezone = $timezone;
        $this->logger = $logger;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->queueResource = $queueResource;
        $this->eventLogger = $eventLogger;
    }

    public function getById(int $campaignId): Campaign
    {
        $campaign = $this->campaignFactory->create();
        $this->campaignResource->load($campaign, $campaignId);
        if (!$campaign->getId()) {
            throw new LocalizedException(__('The campaign with ID "%1" no longer exists.', $campaignId));
        }

        return $campaign;
    }

    public function save(array $data): Campaign
    {
        $campaign = !empty($data['entity_id'])
            ? $this->getById((int)$data['entity_id'])
            : $this->campaignFactory->create();

        $isScheduled = !empty($data['is_scheduled']);
        $scheduleTime = $data['schedule_time'] ?? null;
        
        if (!$isScheduled) {
            $scheduleTime = $this->timezone->date()->format('Y-m-d H:i:s');
        } elseif (!$scheduleTime) {
            throw new LocalizedException(__('Schedule Time is required when scheduling a campaign.'));
        }

        $customerGroupIds = $this->normalizeCustomerGroupIds($data['customer_group_ids'] ?? []);
        $targetType = (string)($data['target_type'] ?? 'groups');
        
        // Parse comma-separated string from select2 or existing array
        $customerIds = [];
        if (!empty($data['customer_ids'])) {
            $rawCustIds = is_string($data['customer_ids']) ? explode(',', $data['customer_ids']) : $data['customer_ids'];
            $customerIds = array_values(array_unique(array_filter(array_map('intval', $rawCustIds))));
        }

        $campaign->addData([
            'campaign_name'       => trim((string)($data['campaign_name'] ?? '')),
            'template_entity_id'  => (int)($data['template_entity_id'] ?? 0),
            'target_type'         => $targetType,
            'customer_group_ids'  => json_encode($customerGroupIds),
            'customer_ids'        => json_encode($customerIds),
            'schedule_time'       => $scheduleTime,
            'is_scheduled'        => $isScheduled,
            'media_handle'        => (string)($data['media_handle'] ?? ''),
            'media_url'           => (string)($data['media_url'] ?? ''),
            'status'              => (string)($data['status'] ?? Campaign::STATUS_PENDING),
            'variable_mapping'    => isset($data['variable_mapping']) && is_array($data['variable_mapping'])
                ? json_encode($data['variable_mapping'])
                : (string)($data['variable_mapping'] ?? ''),
            'trigger_type'        => $data['trigger_type'] ?? 'EXPLICIT_DATE',
            'cron_expression'     => $data['cron_expression'] ?? null,
            'interval_in_months'  => isset($data['interval_in_months']) && $data['interval_in_months'] !== '' ? (int)$data['interval_in_months'] : null,
            'interval_in_weeks'   => isset($data['interval_in_weeks']) && $data['interval_in_weeks'] !== '' ? (int)$data['interval_in_weeks'] : null,
            'interval_in_days'    => isset($data['interval_in_days']) && $data['interval_in_days'] !== '' ? (int)$data['interval_in_days'] : null,
        ]);

        if ($campaign->getData('campaign_name') === '') {
            throw new LocalizedException(__('Campaign Name is required.'));
        }
        if (!(int)$campaign->getData('template_entity_id')) {
            throw new LocalizedException(__('Template is required.'));
        }
        if ($targetType === 'groups' && $customerGroupIds === []) {
            throw new LocalizedException(__('At least one Customer Group is required when Target Type is Groups.'));
        }
        if ($targetType === 'contacts' && $customerIds === []) {
            throw new LocalizedException(__('At least one Contact is required when Target Type is Specific Contacts.'));
        }

        $this->campaignResource->save($campaign);

        // Schedule in external service if scheduled
        if ($isScheduled) {
            try {
                $schedulerId = $campaign->getData('scheduler_id');
                if ($campaign->getData('status') === Campaign::STATUS_PAUSED && $schedulerId) {
                    $this->externalSchedulerService->updateStatus((string)$schedulerId, 'PAUSED');
                } else {
                    $newSchedulerId = $this->externalSchedulerService->scheduleCampaign($campaign);
                    if ($newSchedulerId && $newSchedulerId !== $schedulerId) {
                        $campaign->setData('scheduler_id', $newSchedulerId);
                        $this->campaignResource->save($campaign);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to create/update external schedule: ' . $e->getMessage());
                throw new LocalizedException(__('Campaign saved locally but failed to schedule externally: %1', $e->getMessage()));
            }
        }

        return $campaign;
    }

    public function deleteById(int $campaignId): void
    {
        $campaign = $this->getById($campaignId);
        if ($campaign->getData('scheduler_id')) {
            try {
                $this->externalSchedulerService->deleteSchedule((string)$campaign->getData('scheduler_id'));
            } catch (\Exception $e) {
                $this->logger->error('Failed to delete external schedule: ' . $e->getMessage());
            }
        }
        $this->campaignResource->delete($campaign);
    }

    public function getScheduledCampaigns(): array
    {
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', Campaign::STATUS_PENDING);
        $collection->addFieldToFilter('schedule_time', ['lteq' => $now]);
        $collection->setOrder('schedule_time', 'ASC');

        return $collection->getItems();
    }

    public function markProcessing(Campaign $campaign): void
    {
        $campaign->setData('status', Campaign::STATUS_PROCESSING);
        $this->campaignResource->save($campaign);
    }

    public function markCompleted(Campaign $campaign, int $sentCount, int $failedCount): void
    {
        $campaign->addData([
            'status' => Campaign::STATUS_COMPLETED,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'executed_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
            'error_message' => null,
        ]);
        $this->campaignResource->save($campaign);
    }

    public function markFailed(Campaign $campaign, string $message, int $sentCount = 0, int $failedCount = 0): void
    {
        $campaign->addData([
            'status' => Campaign::STATUS_FAILED,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'executed_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
            'error_message' => mb_substr($message, 0, 65535),
        ]);
        $this->campaignResource->save($campaign);
        $this->logger->error('Campaign failed: ' . $message);
    }

    public function retryFailedItems(Campaign $campaign): void
    {
        $queueCollection = $this->queueCollectionFactory->create();
        $queueCollection->addFieldToFilter('campaign_id', $campaign->getId());
        $queueCollection->addFieldToFilter('status', \Azguards\WhatsAppConnect\Model\CampaignQueue::STATUS_FAILED);

        $retryCount = 0;
        foreach ($queueCollection as $item) {
            $item->setStatus(\Azguards\WhatsAppConnect\Model\CampaignQueue::STATUS_PENDING);
            $item->setErrorMessage(null);
            $this->queueResource->save($item);
            $retryCount++;
        }

        if ($retryCount > 0) {
            $campaign->addData([
                'status' => Campaign::STATUS_PROCESSING,
                'failed_count' => (int)$campaign->getFailedCount() - $retryCount,
                'error_message' => null
            ]);
            $this->campaignResource->save($campaign);
            
            $this->eventLogger->logEventTriggered('campaign_retry_triggered', [
                'campaign_id' => $campaign->getId(),
                'retry_count' => $retryCount
            ]);
            
            $this->logger->info(sprintf('Retry triggered for Campaign ID %s. Reseting %s failed items.', $campaign->getId(), $retryCount));
        }
    }

    /**
     * @param mixed $value
     * @return int[]
     */
    private function normalizeCustomerGroupIds($value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = explode(',', $value);
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $groupIds = array_map('intval', $value);
        $groupIds = array_values(array_filter($groupIds, static fn (int $id): bool => $id > 0));

        return array_values(array_unique($groupIds));
    }
}
