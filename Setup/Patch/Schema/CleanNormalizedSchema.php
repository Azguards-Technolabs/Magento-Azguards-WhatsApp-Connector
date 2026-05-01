<?php
/**
 * Copyright © Azguards, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Drops all WhatsApp tables (normalized + main) so declarative schema
 * can recreate the clean flat structure from db_schema.xml
 */
class CleanNormalizedSchema implements SchemaPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Drop legacy normalized schema tables before declarative recreation.
     *
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();

        // Drop in correct FK order: child tables first, then parent
        $tables = [
            'azguards_whatsapp_component_variables',
            'azguards_whatsapp_template_components',
            'azguards_whatsapp_template_buttons',
            'azguards_whatsapp_templates',
        ];

        // Disable FK checks so we can drop in any order
        $connection->query('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $fullName = $this->resourceConnection->getTableName($table);
            if ($connection->isTableExists($fullName)) {
                $connection->dropTable($fullName);
            }
        }

        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Return schema patch dependencies.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Return schema patch aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
