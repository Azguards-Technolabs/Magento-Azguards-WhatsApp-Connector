<?php
namespace Azguards\WhatsappConnector\Api;

use Azguards\WhatsappConnector\Api\Data\TemplateInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface TemplateRepositoryInterface
{
    public function save(TemplateInterface $template);
    public function getById($id);
    public function getList(SearchCriteriaInterface $searchCriteria);
    public function delete(TemplateInterface $template);
    public function deleteById($id);
}
