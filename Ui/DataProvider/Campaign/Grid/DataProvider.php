<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Ui\DataProvider\Campaign\Grid;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as MagentoDataProvider;

class DataProvider extends MagentoDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
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
    }

    public function addFilter(Filter $filter)
    {
        if ($filter->getField() === 'fulltext') {
            $value = trim((string)$filter->getValue());
            if ($value !== '') {
                // Let the collection handle `fulltext` (we implement it in the grid collection).
                parent::addFilter(
                    $this->filterBuilder
                        ->setField('fulltext')
                        ->setConditionType('like')
                        ->setValue($value)
                        ->create()
                );
            }
            return;
        }

        parent::addFilter($filter);
    }
}
