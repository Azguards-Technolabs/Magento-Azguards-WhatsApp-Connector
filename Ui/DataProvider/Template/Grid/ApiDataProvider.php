<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Ui\DataProvider\Template\Grid;

use Azguards\WhatsAppConnect\Model\Api\TemplateApi;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Psr\Log\LoggerInterface;

class ApiDataProvider extends DataProvider
{
    /**
     * @var TemplateApi
     */
    private $templateApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param TemplateApi $templateApi
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        TemplateApi $templateApi,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->templateApi = $templateApi;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getData()
    {
        try {
            $searchCriteria = $this->getSearchCriteria();
            $page = (int)($searchCriteria->getCurrentPage() ?: 1);
            $limit = (int)($searchCriteria->getPageSize() ?: 20);

            $result = $this->templateApi->getTemplatesPaginated($page, $limit);
            
            $items = [];
            foreach ($result['data'] as $apiData) {
                $items[] = $this->mapApiDataToGrid($apiData);
            }

            return [
                'totalRecords' => $result['total'],
                'items' => $items
            ];
        } catch (\Exception $e) {
            $this->logger->error('ApiDataProvider Error: ' . $e->getMessage());
            return [
                'totalRecords' => 0,
                'items' => []
            ];
        }
    }

    /**
     * Map API data to grid columns
     *
     * @param array $apiData
     * @return array
     */
    private function mapApiDataToGrid(array $apiData): array
    {
        // Extract category
        $category = $apiData['categoryName'] ?? '';
        if (empty($category) && isset($apiData['category']['name'])) {
            $category = $apiData['category']['name'];
        }
        if ($category) {
            $category = ucfirst(strtolower($category));
            if ($category === 'Auth') {
                $category = 'Authentication';
            }
        }

        // Extract language
        $language = $apiData['languageCode'] ?? '';
        if (empty($language) && isset($apiData['language']['code'])) {
            $language = $apiData['language']['code'];
        }

        $templateId = $apiData['id'] ?? $apiData['template_id'] ?? '';

        return [
            'entity_id' => $templateId, // Using template_id as entity_id for grid purposes
            'template_id' => $templateId,
            'template_name' => $apiData['templateName'] ?? $apiData['name'] ?? '',
            'template_type' => $apiData['templateHeaderType'] ?? $apiData['type'] ?? 'TEXT',
            'template_category' => $category,
            'language' => $language ?: 'en_US',
            'status' => $apiData['status'] ?? 'APPROVED',
            'created_at' => $apiData['createdAt'] ?? $apiData['created_at'] ?? ''
        ];
    }
}
