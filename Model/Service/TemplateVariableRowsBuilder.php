<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;

class TemplateVariableRowsBuilder
{
    private ApiHelper $apiHelper;
    private TemplateCollectionFactory $templateCollectionFactory;
    private TemplateVariableExtractor $extractor;

    public function __construct(
        ApiHelper $apiHelper,
        TemplateCollectionFactory $templateCollectionFactory,
        TemplateVariableExtractor $extractor
    ) {
        $this->apiHelper = $apiHelper;
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->extractor = $extractor;
    }

    /**
     * Build config rows for a WhatsApp template using its external `template_id` (UUID from Meta).
     */
    public function buildByExternalTemplateId(string $externalTemplateId): array
    {
        if ($externalTemplateId === '') {
            return [];
        }

        /**
         * Prefer API-provided variable metadata (keeps old behavior: `name`, `order_id` etc),
         * then fallback to DB text extraction (often yields `1`, `2` for Meta numeric placeholders).
         */
        try {
            $apiRows = $this->apiHelper->getTemplateVariable($externalTemplateId);
            if (is_array($apiRows) && !empty($apiRows)) {
                return $apiRows;
            }
        } catch (\Throwable $e) {
            // Ignore and fallback to DB-based extraction.
        }

        $collection = $this->templateCollectionFactory->create();
        $collection->addFieldToFilter('template_id', $externalTemplateId);
        $collection->setPageSize(1);

        $template = $collection->getFirstItem();
        if (!$template || !$template->getId()) {
            return [];
        }

        $examples = [];
        $examplesJson = (string)$template->getData('body_examples_json');
        if ($examplesJson !== '') {
            $decoded = json_decode($examplesJson, true);
            if (is_array($decoded)) {
                $examples = $decoded;
            }
        }

        $variables = $this->extractor->extractFromTemplate($template);
        return $this->extractor->buildRows($variables, $examples);
    }
}
