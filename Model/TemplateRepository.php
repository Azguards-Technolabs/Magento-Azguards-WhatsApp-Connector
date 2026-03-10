<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model;

use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateInterfaceFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;
use Azguards\WhatsAppConnect\Api\Data\TemplateSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class TemplateRepository implements TemplateRepositoryInterface
{
    private $resource;
    private $templateFactory;
    private $collectionFactory;
    private $searchResultsFactory;
    private $collectionProcessor;

    public function __construct(
        TemplateResource $resource,
        TemplateInterfaceFactory $templateFactory,
        CollectionFactory $collectionFactory,
        TemplateSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->templateFactory = $templateFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function save(TemplateInterface $template): TemplateInterface
    {
        try {
            $this->resource->save($template);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $template;
    }

    public function getById(int $entityId): TemplateInterface
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $entityId);
        if (!$template->getId()) {
            throw new NoSuchEntityException(__('The template with ID "%1" doesn\'t exist.', $entityId));
        }
        return $template;
    }

    public function delete(TemplateInterface $template): bool
    {
        try {
            $this->resource->delete($template);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): \Azguards\WhatsAppConnect\Api\Data\TemplateSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
