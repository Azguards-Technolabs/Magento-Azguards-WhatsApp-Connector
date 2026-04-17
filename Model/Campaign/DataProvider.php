<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Campaign;

use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign\CollectionFactory;
use Magento\Backend\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Azguards\WhatsAppConnect\Model\Service\VariableOptionsProvider;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData;
    private Session $session;
    private RequestInterface $request;
    private TimezoneInterface $timezone;
    private UrlInterface $urlBuilder;
    private VariableOptionsProvider $variableOptionsProvider;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        Session $session,
        RequestInterface $request,
        TimezoneInterface $timezone,
        UrlInterface $urlBuilder,
        VariableOptionsProvider $variableOptionsProvider,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->session = $session;
        $this->request = $request;
        $this->timezone = $timezone;
        $this->urlBuilder = $urlBuilder;
        $this->variableOptionsProvider = $variableOptionsProvider;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $campaign) {
            $data = $campaign->getData();
            if (!empty($data['customer_group_ids'])) {
                $groupIds = $data['customer_group_ids'];
                if (is_string($groupIds)) {
                    $groupIds = json_decode($groupIds, true) ?: [];
                }
                if (is_array($groupIds)) {
                    $data['customer_group_ids'] = array_map(function($id) {
                        return (string)$id;
                    }, $groupIds);
                }
            }
            if (!empty($data['customer_ids']) && is_string($data['customer_ids'])) {
                $decoded = json_decode($data['customer_ids'], true);
                if (is_array($decoded)) {
                    $data['customer_ids'] = implode(',', $decoded);
                }
            }
            $data['variable_mapping'] = isset($data['variable_mapping']) ? (string)$data['variable_mapping'] : '';
            
            $now = $this->timezone->date()->format('Y-m-d H:i:s');
            $scheduleTime = (string)($data['schedule_time'] ?? '');
            $data['is_scheduled'] = ($scheduleTime !== '' && $scheduleTime > $now) ? "1" : "0";
            $this->loadedData[$campaign->getId()] = $data;
        }

        $sessionData = $this->session->getFormData(true);
        if (!empty($sessionData)) {
            $campaignId = $sessionData['entity_id'] ?? null;
            $this->loadedData[$campaignId] = $sessionData;
        }

        return $this->loadedData;
    }

    /**
     * Pass authenticated URLs directly to the UI components.
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        
        // Pass URLs to the specific components
        $meta['general']['children']['template_entity_id']['arguments']['data']['config']['varsUrl'] = 
            $this->urlBuilder->getUrl('whatsappconnect/campaign/variables');

        $meta['general']['children']['template_entity_id']['arguments']['data']['config']['uploadUrl'] = 
            $this->urlBuilder->getUrl('whatsappconnect/campaign/upload');

        $meta['general']['children']['template_entity_id']['arguments']['data']['config']['mappingOptions'] =
            $this->variableOptionsProvider->getForEvent('campaign');
            
        $meta['general']['children']['target_type']['arguments']['data']['config']['searchUrl'] = 
            $this->urlBuilder->getUrl('whatsappconnect/campaign/searchcustomers');

        return $meta;
    }
}
