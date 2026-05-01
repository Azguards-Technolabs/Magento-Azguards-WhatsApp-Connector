<?php

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\CampaignQueue;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign as CampaignResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue as QueueResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory as QueueCollectionFactory;
use Azguards\WhatsAppConnect\Model\TemplateFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CampaignWorkerService
{
    /**
     * @var QueueCollectionFactory
     */
    private QueueCollectionFactory $queueCollectionFactory;

    /**
     * @var QueueResource
     */
    private QueueResource $queueResource;

    /**
     * @var CampaignResource
     */
    private CampaignResource $campaignResource;

    /**
     * @var CampaignService
     */
    private CampaignService $campaignService;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

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
     * @var TemplateFactory
     */
    private TemplateFactory $templateFactory;

    /**
     * @var TemplateResource
     */
    private TemplateResource $templateResource;

    /**
     * @var WhatsAppEventLogger
     */
    private WhatsAppEventLogger $eventLogger;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @param QueueCollectionFactory $queueCollectionFactory
     * @param QueueResource $queueResource
     * @param CampaignResource $campaignResource
     * @param CampaignService $campaignService
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerDataBuilder $customerDataBuilder
     * @param CampaignPlaceholderResolver $placeholderResolver
     * @param ApiHelper $apiHelper
     * @param TemplateFactory $templateFactory
     * @param TemplateResource $templateResource
     * @param WhatsAppEventLogger $eventLogger
     * @param DateTime $dateTime
     */
    public function __construct(
        QueueCollectionFactory $queueCollectionFactory,
        QueueResource $queueResource,
        CampaignResource $campaignResource,
        CampaignService $campaignService,
        CustomerRepositoryInterface $customerRepository,
        CustomerDataBuilder $customerDataBuilder,
        CampaignPlaceholderResolver $placeholderResolver,
        ApiHelper $apiHelper,
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        WhatsAppEventLogger $eventLogger,
        DateTime $dateTime
    ) {
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->queueResource = $queueResource;
        $this->campaignResource = $campaignResource;
        $this->campaignService = $campaignService;
        $this->customerRepository = $customerRepository;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->placeholderResolver = $placeholderResolver;
        $this->apiHelper = $apiHelper;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->eventLogger = $eventLogger;
        $this->dateTime = $dateTime;
    }

    /**
     * Process pending queue items in batches.
     *
     * @param string $triggerSource
     * @return void
     */
    public function execute(string $triggerSource = 'Cron'): void
    {
        // Fetch pending items from the queue
        $collection = $this->queueCollectionFactory->create();
        $collection->addFieldToFilter('status', CampaignQueue::STATUS_PENDING);
        $collection->setPageSize(50); // Senior Architect: Rule 1 - Batch limits
        $collection->setOrder('id', 'ASC');

        if ($collection->getSize() === 0) {
            $this->eventLogger->logEventTriggered('campaign_worker_idle', [
                'trigger_source' => $triggerSource,
                'message' => 'No pending queue items found.',
            ]);
            return;
        }

        $this->eventLogger->logEventTriggered('campaign_worker_start', [
            'trigger_source' => $triggerSource,
            'item_count' => $collection->getSize(),
        ]);

        foreach ($collection as $item) {
            $this->processQueueItem($item, $triggerSource);
        }

        $this->eventLogger->logEventTriggered('campaign_worker_end', [
            'trigger_source' => $triggerSource,
            'message' => 'Batch processing completed.',
        ]);
    }

    /**
     * Process a single queue item and update queue/campaign state.
     *
     * @param CampaignQueue $item
     * @param string $triggerSource
     * @return void
     */
    private function processQueueItem(CampaignQueue $item, string $triggerSource = 'Cron'): void
    {
        $campaign = null;
        $campaignId = (int)$item->getCampaignId();

        try {
            if ($campaignId > 0) {
                // For campaign items, we still check if campaign is active
                $campaign = $this->campaignService->getById($campaignId);
                if ($campaign->getStatus() !== Campaign::STATUS_PROCESSING) {
                    return;
                }
            }

            // High Efficiency: Read direct from queue row
            $templateId = (int)$item->getTemplateEntityId();
            if (!$templateId && $campaign) {
                $templateId = (int)$campaign->getData('template_entity_id');
            }

            $template = $this->loadTemplate($templateId);
            $customer = $this->customerRepository->getById((int)$item->getCustomerId());

            // Use enqueued recipient phone
            $phone = (string)$item->getRecipientPhone();
            $userDetail = $this->customerDataBuilder->buildFromCustomer($customer);
            if ($phone) {
                $userDetail['mobileNumber'] = $phone;
                // Ensure contact id is resolved for the overridden phone only when needed.
                $userDetail['contactId'] = '';
            }

            $eventCode = $campaignId ? 'marketing_campaign_' . $campaignId : 'event_message_' . $templateId;

            // Log item processing start
            $this->eventLogger->logPayload('campaign_worker_item_start', [
                'campaign_id' => $campaignId,
                'customer_id' => $item->getCustomerId(),
                'trigger_source' => $triggerSource,
                'recipient_phone' => $userDetail['mobileNumber'],
            ]);

            $this->assertMobileNumber($userDetail);

            // Decode variable mapping from queue row
            $variableMappingRaw = (string)$item->getVariableMapping();
            $variableOverrides = [];
            if ($variableMappingRaw !== '') {
                $decoded = json_decode($variableMappingRaw, true);
                if (is_array($decoded)) {
                    $variableOverrides = $decoded;
                }
            }

            $placeholders = $this->placeholderResolver->build($customer, $userDetail, $template, $variableOverrides);

            // Advanced Senior Logic: Fallback to Template's original media if Campaign has no custom override
            $mediaHandleToUse = (string)$item->getMediaHandle() !== ''
                ? (string)$item->getMediaHandle()
                : ((string)$template->getHeaderHandle() !== '' ? (string)$template->getHeaderHandle() : null);
            $mediaUrlToUse = (string)$item->getMediaUrl() !== ''
                ? (string)$item->getMediaUrl()
                : ((string)$template->getHeaderImage() !== '' ? (string)$template->getHeaderImage() : null);

            // Send Message with Media Overrides or Template Originals
            $response = $this->apiHelper->sendTemplateMessage(
                (string)$template->getTemplateId(),
                $placeholders,
                $userDetail,
                $eventCode,
                $mediaHandleToUse,
                $mediaUrlToUse
            );

            $this->assertSuccessfulResponse($response);

            if ($response['success']) {
                $item->setStatus(CampaignQueue::STATUS_SENT);
                if ($campaign) {
                    $campaign->setSentCount((int)$campaign->getSentCount() + 1);
                }

                $this->eventLogger->logEventTriggered('campaign_item_success', [
                    'campaign_id' => $campaignId,
                    'customer_id' => $item->getCustomerId(),
                    'mobile' => $userDetail['mobileNumber'],
                ]);
            }
        } catch (LocalizedException $e) {
            $item->setStatus(CampaignQueue::STATUS_FAILED);
            $item->setErrorMessage($e->getMessage());

            if ($campaign) {
                $campaign->setFailedCount((int)$campaign->getFailedCount() + 1);
            }
        }

        $item->setProcessedAt($this->dateTime->gmtDate());
        $this->queueResource->save($item);

        // Update campaign totals and check for completion
        if (isset($campaign)) {
            $this->checkCampaignCompletion($campaign);
            $this->campaignResource->save($campaign);
        }
    }

    /**
     * Complete the campaign when no queue items remain.
     *
     * @param Campaign $campaign
     * @return void
     */
    private function checkCampaignCompletion(Campaign $campaign): void
    {
        $collection = $this->queueCollectionFactory->create();
        $collection->addFieldToFilter('campaign_id', $campaign->getId());
        $collection->addFieldToFilter('status', CampaignQueue::STATUS_PENDING);

        if ($collection->getSize() === 0) {
            $campaign->setStatus(Campaign::STATUS_COMPLETED);
            $campaign->setExecutedAt($this->dateTime->gmtDate());
        }
    }

    /**
     * Load a template model by entity ID.
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
     * Ensure the resolved user detail contains a mobile number.
     *
     * @param array $userDetail
     * @return void
     * @throws LocalizedException
     */
    private function assertMobileNumber(array $userDetail): void
    {
        if (empty($userDetail['mobileNumber'])) {
            throw new LocalizedException(__('Missing mobile number'));
        }
    }

    /**
     * Ensure the send-template response indicates success.
     *
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    private function assertSuccessfulResponse(array $response): void
    {
        if (!empty($response['success'])) {
            return;
        }

        throw new LocalizedException(__((string)($response['message'] ?? 'Unknown Error')));
    }
}
