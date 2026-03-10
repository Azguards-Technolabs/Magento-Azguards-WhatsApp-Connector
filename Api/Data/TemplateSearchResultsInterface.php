<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TemplateSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface[]
     */
    public function getItems();

    /**
     * @param \Azguards\WhatsAppConnect\Api\Data\TemplateInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
