<?php
namespace Azguards\WhatsappConnector\Model;

use Azguards\WhatsappConnector\Api\Data\TemplateInterface;
use Azguards\WhatsappConnector\Api\TemplateRepositoryInterface;
use Azguards\WhatsappConnector\Model\ResourceModel\Template as TemplateResource;
use Azguards\WhatsappConnector\Model\TemplateFactory;
use Azguards\WhatsappConnector\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class TemplateRepository implements TemplateRepositoryInterface
{
    protected $resource;
    protected $templateFactory;
    protected $templateCollectionFactory;
    protected $searchResultsFactory;
    protected $collectionProcessor;

    public function __construct(
        TemplateResource $resource,
        TemplateFactory $templateFactory,
        TemplateCollectionFactory $templateCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->templateFactory = $templateFactory;
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function save(TemplateInterface $template)
    {
        try {
            $this->resource->save($template);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $template;
    }

    public function getById($id)
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $id);
        if (!$template->getId()) {
            throw new NoSuchEntityException(__('Template with id "%1" does not exist.', $id));
        }
        return $template;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->templateCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(TemplateInterface $template)
    {
        try {
            $this->resource->delete($template);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
