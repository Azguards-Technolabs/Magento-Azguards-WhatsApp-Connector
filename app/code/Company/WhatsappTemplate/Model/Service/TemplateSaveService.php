<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model\Service;

use Company\WhatsappTemplate\Api\TemplateRepositoryInterface;
use Company\WhatsappTemplate\Api\Data\TemplateInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class TemplateSaveService
{
    private $templateRepository;
    private $templateFactory;
    private $resourceConnection;
    private $logger;

    public function __construct(
        TemplateRepositoryInterface $templateRepository,
        TemplateInterfaceFactory $templateFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->templateRepository = $templateRepository;
        $this->templateFactory = $templateFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Save a single template with nested components, variables, and buttons.
     * Transactional bulk save.
     */
    public function saveTemplateWithRelations(array $templateData, array $componentsData = [], array $buttonsData = []): \Company\WhatsappTemplate\Api\Data\TemplateInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            // 1. Save Template
            $template = $this->templateFactory->create();
            $template->setData($templateData);
            $this->templateRepository->save($template);

            $templateId = (int)$template->getId();

            // 2. Save Components & Variables (Bulk)
            if (!empty($componentsData)) {
                $this->saveComponentsAndVariables($connection, $templateId, $componentsData);
            }

            // 3. Save Buttons (Bulk)
            if (!empty($buttonsData)) {
                $this->saveButtons($connection, $templateId, $buttonsData);
            }

            $connection->commit();
            return $template;
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error('Failed to save WhatsApp Template: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to save template and relations. Check logs for details.'));
        }
    }

    /**
     * Handles bulk saving for components and their nested variables.
     */
    private function saveComponentsAndVariables(AdapterInterface $connection, int $templateId, array $componentsData): void
    {
        $componentsTable = $this->resourceConnection->getTableName('whatsapp_template_components');
        $variablesTable = $this->resourceConnection->getTableName('whatsapp_component_variables');

        $componentsToInsert = [];
        $variablesPayloadQueue = []; // Hold variables data until components are saved and IDs retrieved

        // As we need the component_id for the variables, and insertMultiple doesn't return the lastInsertId per row reliably for bulk,
        // we can either insert components one by one or insert multiple, then fetch their IDs based on sort_order/type to attach variables.
        // For pure bulk, we must insert components first. Let's do a loop for components if they contain variables to reliably get IDs,
        // or we could use the new IDs if we batch. In typical cases, a template has a small number of components (1-10).
        // Let's use bulk for components that have no variables, but since relations matter, let's optimize reasonably.

        foreach ($componentsData as $index => $comp) {
            $comp['template_id'] = $templateId;
            $compVariables = $comp['variables'] ?? [];
            unset($comp['variables']); // Remove from component array before insert

            // Insert single component to retrieve its auto-increment ID
            $connection->insert($componentsTable, $comp);
            $componentId = (int)$connection->lastInsertId();

            if (!empty($compVariables)) {
                foreach ($compVariables as $var) {
                    $var['component_id'] = $componentId;
                    $variablesPayloadQueue[] = $var;
                }
            }
        }

        // 3. Bulk insert Variables
        if (!empty($variablesPayloadQueue)) {
            $connection->insertMultiple($variablesTable, $variablesPayloadQueue);
        }
    }

    /**
     * Handles bulk saving for buttons.
     */
    private function saveButtons(AdapterInterface $connection, int $templateId, array $buttonsData): void
    {
        $buttonsTable = $this->resourceConnection->getTableName('whatsapp_template_buttons');
        $buttonsToInsert = [];

        foreach ($buttonsData as $btn) {
            $btn['template_id'] = $templateId;
            $buttonsToInsert[] = $btn;
        }

        if (!empty($buttonsToInsert)) {
            $connection->insertMultiple($buttonsTable, $buttonsToInsert);
        }
    }
}
