<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\TemplateFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ExternalSchedulerService
{
    private ApiHelper $apiHelper;
    private Curl $curl;
    private LoggerInterface $logger;
    private TemplateFactory $templateFactory;
    private TemplateResource $templateResource;
    private CampaignPlaceholderResolver $placeholderResolver;
    private CustomerDataBuilder $customerDataBuilder;
    private \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory;

    private const API_BASE_URL = 'https://dev-api.bizzupapp.com/scheduler-service/api/v1/schedule';

    public function __construct(
        ApiHelper $apiHelper,
        Curl $curl,
        LoggerInterface $logger,
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        CampaignPlaceholderResolver $placeholderResolver,
        CustomerDataBuilder $customerDataBuilder,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->apiHelper = $apiHelper;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->placeholderResolver = $placeholderResolver;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    public function scheduleCampaign(Campaign $campaign): string
    {
        $payload = $this->buildPayload($campaign);

        $schedulerId = $campaign->getData('scheduler_id');
        $token = $this->apiHelper->getToken() ?: $this->apiHelper->getConnectorAuthentication();

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]);

        if ($schedulerId) {
            // Update existing schedule
            $url = self::API_BASE_URL . '/' . $schedulerId;
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
            $this->curl->post($url, json_encode($payload));
        } else {
            // Create new schedule
            $url = self::API_BASE_URL;
            $this->curl->post($url, json_encode($payload));
        }

        $responseBody = $this->curl->getBody();
        $statusCode = $this->curl->getStatus();

        $this->logger->info('ExternalSchedulerService: ' . ($schedulerId ? 'Update' : 'Create') . ' Response', [
            'status' => $statusCode,
            'body' => $responseBody,
            'payload' => $payload
        ]);

        $responseData = json_decode($responseBody, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $errorMsg = $this->apiHelper->extractErrorMessage($responseData ?? []);
            throw new LocalizedException(__('Failed to schedule campaign in external service: %1', $errorMsg));
        }

        // Assuming response returns the created scheduler_id in 'id' or 'result.id'
        $newSchedulerId = $responseData['id'] ?? $responseData['result']['id'] ?? $schedulerId;

        if (!$newSchedulerId) {
             $this->logger->warning('ExternalSchedulerService: No scheduler ID returned from API.');
             return '';
        }

        return (string)$newSchedulerId;
    }

    public function updateStatus(string $schedulerId, string $status): void
    {
         if (!$schedulerId) {
             return;
         }

         $payload = ['status' => $status];
         $token = $this->apiHelper->getToken() ?: $this->apiHelper->getConnectorAuthentication();

         $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
         ]);

         // Assuming the ID is passed in the URL for update or maybe just in payload.
         // Adjust based on exact API spec. For now, assuming POST to base URL or PUT.
         // In prompt: PUT payload: { "status": "PAUSED" }
         $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
         $url = self::API_BASE_URL . '/' . $schedulerId;
         $this->curl->post($url, json_encode($payload));

         $this->logger->info('ExternalSchedulerService: Update Status Response', [
             'status_code' => $this->curl->getStatus(),
             'body' => $this->curl->getBody()
         ]);
    }

    public function deleteSchedule(string $schedulerId): void
    {
        if (!$schedulerId) {
            return;
        }

        $token = $this->apiHelper->getToken() ?: $this->apiHelper->getConnectorAuthentication();

        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]);

        $url = self::API_BASE_URL . '/' . $schedulerId;
        $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->curl->post($url, '');

        $this->logger->info('ExternalSchedulerService: Delete Response', [
            'status' => $this->curl->getStatus(),
            'body' => $this->curl->getBody()
        ]);
    }

    private function buildPayload(Campaign $campaign): array
    {
        $triggerType = $campaign->getData('trigger_type') ?: 'EXPLICIT_DATE';

        // Extract userId and businessId from JWT token
        $token = $this->apiHelper->getToken() ?: $this->apiHelper->getConnectorAuthentication();
        $userId = '';
        $businessId = '';
        if ($token && strpos($token, '.') !== false) {
            $parts = explode('.', $token);
            if (count($parts) >= 2) {
                $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
                $decoded = json_decode($payloadJson, true);
                if ($decoded) {
                    $userId = $decoded['userId'] ?? $decoded['id'] ?? '';
                    $businessId = $decoded['businessId'] ?? $decoded['business_id'] ?? '';
                }
            }
        }

        $triggerConfig = [
            'trigger_type' => $triggerType,
            'description' => $campaign->getData('campaign_name'),
            'time_zone' => 'UTC' // Store timezone if possible, default UTC
        ];

        if ($triggerType === 'EXPLICIT_DATE') {
             // Convert local schedule_time to UTC string or assuming it's UTC
             $scheduleTime = $campaign->getData('schedule_time');
             $date = new \DateTime($scheduleTime, new \DateTimeZone('UTC')); // Adjust based on Magento timezone handling
             $triggerConfig['execution_date_time'] = $date->format('Y-m-d\TH:i:s\Z');
        } else {
             $triggerConfig['cron_expression'] = $campaign->getData('cron_expression');
             if ($campaign->getData('interval_in_months') !== null) {
                 $triggerConfig['interval_in_months'] = (int)$campaign->getData('interval_in_months');
             }
             if ($campaign->getData('interval_in_weeks') !== null) {
                 $triggerConfig['interval_in_weeks'] = (int)$campaign->getData('interval_in_weeks');
             }
             if ($campaign->getData('interval_in_days') !== null) {
                 $triggerConfig['interval_in_days'] = (int)$campaign->getData('interval_in_days');
             }
        }

        // Job Data
        $templateId = (int)$campaign->getData('template_entity_id');
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);

        $customers = $this->getCustomers($campaign);
        $contactNumber = [];
        $firstCustomerDetail = null;

        foreach ($customers as $customer) {
             $detail = $this->customerDataBuilder->buildFromCustomer($customer);
             if ($detail['mobileNumber']) {
                 $contactNumber[$detail['mobileNumber']] = trim($detail['firstName'] . ' ' . $detail['lastName']);
                 if (!$firstCustomerDetail) {
                     $firstCustomerDetail = $detail;
                 }
             }
        }

        $variableMapping = $campaign->getData('variable_mapping');
        if (is_string($variableMapping)) {
            $variableMapping = json_decode($variableMapping, true) ?: [];
        }

        // Resolve Placeholders
        $attributes = [];
        if ($firstCustomerDetail) {
             $resolved = $this->placeholderResolver->resolve($template, $firstCustomerDetail, $variableMapping);
             // Transform resolved variables to API format
             // Assuming $resolved contains Header and Body keys
             if (isset($resolved['Header']) && is_array($resolved['Header'])) {
                 $placeholders = [];
                 foreach ($resolved['Header'] as $i => $val) {
                     $placeholders[] = [
                         'key' => (string)($i+1),
                         'value' => $val,
                         'is_user_attribute' => false,
                         'attribute_name' => '' // Update if dynamic
                     ];
                 }
                 if (!empty($placeholders)) {
                     $attributes['header'] = [
                         'order' => 0,
                         'placeholders' => $placeholders
                     ];
                 }
             }
             if (isset($resolved['Body']) && is_array($resolved['Body'])) {
                 $placeholders = [];
                 foreach ($resolved['Body'] as $i => $val) {
                     $placeholders[] = [
                         'key' => (string)($i+1),
                         'value' => $val,
                         'is_user_attribute' => false,
                         'attribute_name' => ''
                     ];
                 }
                 if (!empty($placeholders)) {
                     $attributes['body'] = [
                         'order' => 1,
                         'placeholders' => $placeholders
                     ];
                 }
             }
        }

        $jobData = [
            'templateId' => $template->getData('template_id'),
            'contactNumber' => $contactNumber,
            'attributes' => $attributes,
            'userId' => $userId,
            'businessId' => $businessId
        ];

        return [
            'status' => 'SCHEDULED', // Or ACTIVE based on docs
            'trigger_config' => $triggerConfig,
            'job_data' => $jobData
        ];
    }

    private function getCustomers(Campaign $campaign): array
    {
        $targetType = $campaign->getData('target_type') ?: 'groups';
        if ($targetType === 'contacts') {
             $customerIds = $campaign->getData('customer_ids');
             if (is_string($customerIds)) {
                 $customerIds = json_decode($customerIds, true) ?: explode(',', $customerIds);
             }
             if (empty($customerIds)) return [];

             $collection = $this->customerCollectionFactory->create();
             $collection->addFieldToFilter('entity_id', ['in' => $customerIds]);
             $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'default_billing', 'whatsapp_sync_status']);
             $collection->addAttributeToFilter('whatsapp_sync_status', 1);
             return array_values($collection->getItems());
        } else {
             $groupIds = $campaign->getData('customer_group_ids');
             if (is_string($groupIds)) {
                 $groupIds = json_decode($groupIds, true) ?: explode(',', $groupIds);
             }
             if (empty($groupIds)) return [];

             $collection = $this->customerCollectionFactory->create();
             $collection->addFieldToFilter('group_id', ['in' => $groupIds]);
             $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'default_billing', 'whatsapp_sync_status']);
             $collection->addAttributeToFilter('whatsapp_sync_status', 1);
             return array_values($collection->getItems());
        }
    }
}
