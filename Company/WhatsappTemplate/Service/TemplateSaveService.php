<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Service;

use Company\WhatsappTemplate\Api\Data\TemplateInterface;
use Company\WhatsappTemplate\Api\TemplateRepositoryInterface;
use Company\WhatsappTemplate\Model\ResourceModel\TemplateComponent as ComponentResource;
use Company\WhatsappTemplate\Model\ResourceModel\TemplateButton as ButtonResource;
use Company\WhatsappTemplate\Model\ResourceModel\ComponentVariable as VariableResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

/**
 * Class TemplateSaveService
 */
class TemplateSaveService
{
    /**
     * @var TemplateRepositoryInterface
     */
    private $templateRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TemplateRepositoryInterface $templateRepository
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        TemplateRepositoryInterface $templateRepository,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->templateRepository = $templateRepository;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Save Template with its components, variables, and buttons in a single transaction.
     *
     * @param TemplateInterface $template
     * @param array $componentsData
     * @param array $buttonsData
     * @return TemplateInterface
     * @throws CouldNotSaveException
     */
    public function saveFullTemplate(TemplateInterface $template, array $componentsData, array $buttonsData)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            // 1. Save Template
            $this->templateRepository->save($template);
            $templateId = $template->getId();

            // 2. Save Template Components and their Variables
            $this->processComponents($templateId, $componentsData);

            // 3. Save Template Buttons
            $this->processButtons($templateId, $buttonsData);

            $connection->commit();
            return $template;
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error('Error saving full WhatsApp template: ' . $e->getMessage());
            throw new CouldNotSaveException(__('Could not save WhatsApp template: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Process and save components and their associated variables
     *
     * @param int $templateId
     * @param array $componentsData
     * @return void
     */
    private function processComponents($templateId, array $componentsData)
    {
        $connection = $this->resourceConnection->getConnection();
        $componentTableName = $this->resourceConnection->getTableName('whatsapp_template_components');
        $variableTableName = $this->resourceConnection->getTableName('whatsapp_component_variables');

        foreach ($componentsData as $componentData) {
            $variables = $componentData['variables'] ?? [];
            unset($componentData['variables']);

            $componentData['template_id'] = $templateId;
            $componentData['created_at'] = date('Y-m-d H:i:s');
            $componentData['updated_at'] = date('Y-m-d H:i:s');

            $connection->insert($componentTableName, $componentData);
            $componentId = $connection->lastInsertId($componentTableName);

            if (!empty($variables)) {
                $variablesToInsert = [];
                foreach ($variables as $variable) {
                    $variable['component_id'] = $componentId;
                    $variable['created_at'] = date('Y-m-d H:i:s');
                    $variable['updated_at'] = date('Y-m-d H:i:s');
                    $variablesToInsert[] = $variable;
                }
                $connection->insertMultiple($variableTableName, $variablesToInsert);
            }
        }
    }

    /**
     * Process and save template buttons using bulk insert
     *
     * @param int $templateId
     * @param array $buttonsData
     * @return void
     */
    private function processButtons($templateId, array $buttonsData)
    {
        if (empty($buttonsData)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $buttonTableName = $this->resourceConnection->getTableName('whatsapp_template_buttons');

        $buttonsToInsert = [];
        foreach ($buttonsData as $button) {
            $button['template_id'] = $templateId;
            $button['created_at'] = date('Y-m-d H:i:s');
            $button['updated_at'] = date('Y-m-d H:i:s');
            $buttonsToInsert[] = $button;
        }

        $connection->insertMultiple($buttonTableName, $buttonsToInsert);
    }
}
