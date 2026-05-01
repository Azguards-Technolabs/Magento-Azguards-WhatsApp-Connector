<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TemplateSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get template items.
     *
     * @return \Azguards\WhatsAppConnect\Api\Data\TemplateInterface[]
     */
    public function getItems();

    /**
     * Set template items.
     *
     * @param \Azguards\WhatsAppConnect\Api\Data\TemplateInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
