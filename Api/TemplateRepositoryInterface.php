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
    public function save(TemplateInterface $template): TemplateInterface;
    public function getById(int $entityId): TemplateInterface;
    public function delete(TemplateInterface $template): bool;
    public function deleteById(int $entityId): bool;
    public function getList(SearchCriteriaInterface $searchCriteria): TemplateSearchResultsInterface;
}
