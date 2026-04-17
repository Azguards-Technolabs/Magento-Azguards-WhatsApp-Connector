<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\ResourceModel\Campaign\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

class Collection extends SearchResult
{
    /**
     * UI grid "search" box uses a virtual `fulltext` filter. Handle it explicitly.
     *
     * @param string|array $field
     * @param string|array|null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'fulltext') {
            $value = '';
            if (is_array($condition)) {
                $value = (string)($condition['like'] ?? $condition['eq'] ?? '');
            } elseif (is_string($condition)) {
                $value = $condition;
            }

            $value = trim($value);
            if ($value !== '') {
                // Escape LIKE wildcards so user input like `foo_bar` matches literally.
                $escaped = addcslashes($value, "\\%_");
                $likeValue = '%' . $escaped . '%';
                $adapter = $this->getConnection();
                $conditions = [
                    $adapter->quoteInto("main_table.campaign_name LIKE ? ESCAPE '\\\\'", $likeValue),
                    $adapter->quoteInto("template_table.template_name LIKE ? ESCAPE '\\\\'", $likeValue),
                    $adapter->quoteInto("main_table.status LIKE ? ESCAPE '\\\\'", $likeValue),
                    $adapter->quoteInto("CAST(main_table.entity_id AS CHAR) LIKE ? ESCAPE '\\\\'", $likeValue),
                ];
                $this->getSelect()->where('(' . implode(' OR ', $conditions) . ')');
            }

            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable = 'azguards_whatsapp_campaigns',
        $resourceModel = \Azguards\WhatsAppConnect\Model\ResourceModel\Campaign::class,
        $identifierName = null,
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        // Avoid ambiguous column filters once we join other tables (e.g. template_table.entity_id).
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        $this->addFilterToMap('campaign_name', 'main_table.campaign_name');
        $this->addFilterToMap('template_name', 'template_table.template_name');
        $this->addFilterToMap('customer_group_ids', 'main_table.customer_group_ids');
        $this->addFilterToMap('schedule_time', 'main_table.schedule_time');
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('sent_count', 'main_table.sent_count');
        $this->addFilterToMap('failed_count', 'main_table.failed_count');
        $this->getSelect()->joinLeft(
            ['template_table' => $this->getTable('azguards_whatsapp_templates')],
            'main_table.template_entity_id = template_table.entity_id',
            ['template_name' => 'template_name']
        );

        return $this;
    }
}
