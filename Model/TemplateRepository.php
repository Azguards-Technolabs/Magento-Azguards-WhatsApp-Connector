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
use Psr\Log\LoggerInterface;

class TemplateRepository implements TemplateRepositoryInterface
{
    /**
     * @var TemplateResource
     */
    private $resource;

    /**
     * @var TemplateInterfaceFactory
     */
    private $templateFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var TemplateSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TemplateResource $resource
     * @param TemplateInterfaceFactory $templateFactory
     * @param CollectionFactory $collectionFactory
     * @param TemplateSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        TemplateResource $resource,
        TemplateInterfaceFactory $templateFactory,
        CollectionFactory $collectionFactory,
        TemplateSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->templateFactory = $templateFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->logger = $logger;
    }

    /**
     * Save template
     *
     * @param TemplateInterface $template
     * @return TemplateInterface
     * @throws CouldNotSaveException
     */
    public function save(TemplateInterface $template): TemplateInterface
    {
        try {
            $this->resource->save($template);
        } catch (\Exception $e) {
            $this->logger->error("TemplateRepository::save - Error: " . $e->getMessage());
            $this->logger->error("TemplateRepository::save - Data: " . json_encode($template->getData()));
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $template;
    }

    /**
     * Get template by ID
     *
     * @param int $entityId
     * @return TemplateInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): TemplateInterface
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $entityId);
        if (!$template->getId()) {
            throw new NoSuchEntityException(__('The template with ID "%1" doesn\'t exist.', $entityId));
        }
        return $template;
    }

    /**
     * Delete template
     *
     * @param TemplateInterface $template
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TemplateInterface $template): bool
    {
        try {
            $this->resource->delete($template);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    /**
     * Delete template by ID
     *
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }

    /**
     * Get template list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateSearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): \Azguards\WhatsAppConnect\Api\Data\TemplateSearchResultsInterface {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
