<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\TemplateFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Azguards\WhatsAppConnect\Model\CampaignQueue;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory as QueueCollectionFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue as QueueResource;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

/**
 * Service for scheduling and processing marketing campaigns.
 */
class CampaignSchedulerService
{
    /**
     * @var CampaignService
     */
    private CampaignService $campaignService;

    /**
     * @var CustomerCollectionFactory
     */
    private CustomerCollectionFactory $customerCollectionFactory;

    /**
     * @var TemplateFactory
     */
    private TemplateFactory $templateFactory;

    /**
     * @var TemplateResource
     */
    private TemplateResource $templateResource;

    /**
     * @var CustomerDataBuilder
     */
    private CustomerDataBuilder $customerDataBuilder;

    /**
     * @var CampaignPlaceholderResolver
     */
    private CampaignPlaceholderResolver $placeholderResolver;

    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var WhatsAppEventLogger
     */
    private WhatsAppEventLogger $eventLogger;

    /**
     * @var QueueCollectionFactory
     */
    private QueueCollectionFactory $queueCollectionFactory;

    /**
     * @var QueueResource
     */
    private QueueResource $queueResource;

    /**
     * @var MessageDispatcher
     */
    private MessageDispatcher $messageDispatcher;

    /**
     * @param CampaignService $campaignService
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param TemplateFactory $templateFactory
     * @param TemplateResource $templateResource
     * @param CustomerDataBuilder $customerDataBuilder
     * @param CampaignPlaceholderResolver $placeholderResolver
     * @param ApiHelper $apiHelper
     * @param WhatsAppEventLogger $eventLogger
     * @param QueueCollectionFactory $queueCollectionFactory
     * @param QueueResource $queueResource
     * @param MessageDispatcher $messageDispatcher
     */
    public function __construct(
        CampaignService $campaignService,
        CustomerCollectionFactory $customerCollectionFactory,
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        CustomerDataBuilder $customerDataBuilder,
        CampaignPlaceholderResolver $placeholderResolver,
        ApiHelper $apiHelper,
        WhatsAppEventLogger $eventLogger,
        QueueCollectionFactory $queueCollectionFactory,
        QueueResource $queueResource,
        MessageDispatcher $messageDispatcher
    ) {
        $this->campaignService = $campaignService;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->placeholderResolver = $placeholderResolver;
        $this->apiHelper = $apiHelper;
        $this->eventLogger = $eventLogger;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->queueResource = $queueResource;
        $this->messageDispatcher = $messageDispatcher;
    }

    /**
     * Execute campaign scheduling.
     *
     * @param string $triggerSource
     * @return void
     */
    public function execute(string $triggerSource = 'Cron'): void
    {
        foreach ($this->campaignService->getScheduledCampaigns() as $campaign) {
            // Skip campaigns that are managed by the external scheduler
            if ($campaign->getData('scheduler_id')) {
                continue;
            }
            $this->processCampaign($campaign, $triggerSource);
        }
    }

    /**
     * Process a single campaign.
     *
     * @param Campaign $campaign
     * @param string $triggerSource
     * @return void
     */
    public function processCampaign(Campaign $campaign, string $triggerSource = 'Immediate'): void
    {
        $eventCode = 'marketing_campaign_queue_' . $campaign->getId();
        
        $this->eventLogger->logEventTriggered($eventCode, [
            'campaign_name' => $campaign->getData('campaign_name'),
            'trigger_source' => $triggerSource,
            'is_scheduled' => (bool)$campaign->getData('is_scheduled'),
        ]);

        try {
            $targetType = $campaign->getData('target_type') ?: 'groups';
            $customers = [];

            if ($targetType === 'contacts') {
                $customerIds = $this->getCustomerIds($campaign);
                if ($customerIds === []) {
                    throw new \RuntimeException('Campaign target contacts are missing.');
                }
                $customers = $this->getCustomersByIds($customerIds);
            } else {
                $customerGroupIds = $this->getCustomerGroupIds($campaign);
                if ($customerGroupIds === []) {
                    throw new \RuntimeException('Campaign customer groups are missing.');
                }
                $customers = $this->getCustomersByGroups($customerGroupIds);
            }

            $targetCustomerIds = array_map(fn($customer) => (int)$customer->getId(), $customers);
            $existingQueueCollection = $this->queueCollectionFactory->create();
            $existingQueueCollection->addFieldToFilter('campaign_id', $campaign->getId());
            
            // 1. Remove Pending items that are NO LONGER in the target audience (Audience Sync)
            foreach ($existingQueueCollection as $item) {
                $customerId = (int)$item->getCustomerId();
                if ($item->getStatus() === CampaignQueue::STATUS_PENDING && !in_array($customerId, $targetCustomerIds)) {
                    $this->queueResource->delete($item);
                }
            }

            // 2. Delegate Enqueuing of new items to Dispatcher
            $this->messageDispatcher->enqueueCampaignItems($campaign, $customers);

            $this->campaignService->markProcessing($campaign);
        } catch (\Throwable $exception) {
            $this->eventLogger->logError($eventCode, $exception->getMessage(), [
                'campaign_id' => $campaign->getId(),
            ]);
            $this->campaignService->markFailed($campaign, $exception->getMessage());
        }
    }

    /**
     * Load template by ID.
     *
     * @param int $templateId
     * @return \Azguards\WhatsAppConnect\Model\Template
     */
    private function loadTemplate(int $templateId)
    {
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);
        return $template;
    }

    /**
     * Get targeted customer group IDs.
     *
     * @param Campaign $campaign
     * @return int[]
     */
    private function getCustomerGroupIds(Campaign $campaign): array
    {
        $rawValue = $campaign->getData('customer_group_ids');
        if (is_string($rawValue) && $rawValue !== '') {
            $decoded = json_decode($rawValue, true);
            if (is_array($decoded)) {
                $rawValue = $decoded;
            } else {
                $rawValue = explode(',', $rawValue);
            }
        }

        if (!is_array($rawValue)) {
            $rawValue = [$rawValue];
        }

        $groupIds = array_map('intval', $rawValue);
        $groupIds = array_values(array_filter($groupIds, static fn (int $id): bool => $id > 0));

        return array_values(array_unique($groupIds));
    }

    /**
     * Get customers by group IDs.
     *
     * @param array $customerGroupIds
     * @return array
     */
    private function getCustomersByGroups(array $customerGroupIds): array
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'default_billing']);
        $collection->addFieldToFilter('group_id', ['in' => $customerGroupIds]);
        $collection->addAttributeToFilter('whatsapp_sync_status', 1);

        return array_values($collection->getItems());
    }

    /**
     * Get targeted customer IDs.
     *
     * @param Campaign $campaign
     * @return int[]
     */
    private function getCustomerIds(Campaign $campaign): array
    {
        $rawValue = $campaign->getData('customer_ids');
        if (is_string($rawValue) && $rawValue !== '') {
            $decoded = json_decode($rawValue, true);
            if (is_array($decoded)) {
                $rawValue = $decoded;
            } else {
                $rawValue = explode(',', (string)$rawValue);
            }
        }

        if (!is_array($rawValue)) {
            $rawValue = [$rawValue];
        }

        $customerIds = array_map('intval', $rawValue);
        return array_values(array_unique(array_filter($customerIds, static fn (int $id): bool => $id > 0)));
    }

    /**
     * Get customers by IDs.
     *
     * @param array $customerIds
     * @return array
     */
    private function getCustomersByIds(array $customerIds): array
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'default_billing']);
        $collection->addFieldToFilter('entity_id', ['in' => $customerIds]);
        $collection->addAttributeToFilter('whatsapp_sync_status', 1);

        return array_values($collection->getItems());
    }
}
