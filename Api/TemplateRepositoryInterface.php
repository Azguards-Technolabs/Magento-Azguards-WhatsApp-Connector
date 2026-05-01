<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Azguards\WhatsAppConnect\Api\Data\TemplateSearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface TemplateRepositoryInterface
{
    /**
     * Save a template entity.
     *
     * @param TemplateInterface $template
     * @return TemplateInterface
     * @throws CouldNotSaveException
     */
    public function save(TemplateInterface $template): TemplateInterface;

    /**
     * Retrieve a template by ID.
     *
     * @param int $entityId
     * @return TemplateInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): TemplateInterface;

    /**
     * Delete a template entity.
     *
     * @param TemplateInterface $template
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TemplateInterface $template): bool;

    /**
     * Delete a template by ID.
     *
     * @param int $entityId
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Retrieve templates matching search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return TemplateSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TemplateSearchResultsInterface;
}
