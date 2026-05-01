<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Azguards\WhatsAppConnect\Model\TemplateFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableRowsBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

class Variables extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var TemplateFactory
     */
    private TemplateFactory $templateFactory;

    /**
     * @var TemplateResource
     */
    private TemplateResource $templateResource;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var TemplateVariableRowsBuilder
     */
    private TemplateVariableRowsBuilder $variableRowsBuilder;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateFactory $templateFactory
     * @param TemplateResource $templateResource
     * @param StoreManagerInterface $storeManager
     * @param TemplateVariableRowsBuilder $variableRowsBuilder
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        StoreManagerInterface $storeManager,
        TemplateVariableRowsBuilder $variableRowsBuilder
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->storeManager = $storeManager;
        $this->variableRowsBuilder = $variableRowsBuilder;
    }

    /**
     * Return template variables for the selected campaign template.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $entityId = (int)$this->getRequest()->getParam('entity_id');
            if (!$entityId) {
                return $result->setData(['variables' => []]);
            }

            $template = $this->templateFactory->create();
            $this->templateResource->load($template, $entityId);

            if (!$template->getId()) {
                return $result->setData(['variables' => [], 'error' => 'Not found']);
            }

            // Prefer API/DB-built variable rows (keeps `name`, `order_id` instead of `1`, `2`).
            $externalTemplateId = (string)$template->getData('template_id');
            $rows = $this->variableRowsBuilder->buildByExternalTemplateId($externalTemplateId);
            if (!empty($rows)) {
                $variables = [];
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $name = (string)($row['type'] ?? $row['title'] ?? '');
                    if ($name === '') {
                        continue;
                    }
                    $variables[] = [
                        'name' => $name,
                        'label' => (string)($row['title'] ?? $name),
                        'position' => (string)($row['order'] ?? ''),
                    ];
                }

                $headerImage = (string)$template->getData('header_image');
                $headerFormat = strtoupper((string)$template->getData('header_format'));
                if ($headerImage && $headerFormat === 'IMAGE') {
                    if (!filter_var($headerImage, FILTER_VALIDATE_URL)) {
                        $headerImage = $this->storeManager->getStore()
                            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . ltrim($headerImage, '/');
                    }
                }

                return $result->setData([
                    'variables' => $variables,
                    'header_format' => $headerFormat ?: 'TEXT',
                    'header_image' => $headerImage ?: null,
                    'body' => (string)$template->getData('body')
                ]);
            }

            $body = (string)$template->getData('body');
            $text = implode(' ', array_filter([
                (string)$template->getData('header'),
                $body,
                (string)$template->getData('footer'),
            ]));

            preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $text, $matches);
            $uniqueVars = array_values(array_unique($matches[1] ?? []));

            $examples = [];
            $examplesJson = $template->getData('body_examples_json');
            if ($examplesJson) {
                $examples = json_decode((string)$examplesJson, true) ?: [];
            }

            $variables = [];
            foreach ($uniqueVars as $index => $varName) {
                $label = $varName;
                // If it's a numeric variable like {{1}}, check if we have an example for it
                if (is_numeric($varName)) {
                    $exampleIdx = (int)$varName - 1;
                    if (isset($examples[$exampleIdx])) {
                        $label = $examples[$exampleIdx];
                    }
                }

                $variables[] = [
                    'name' => $varName,
                    'label' => $label
                ];
            }

            $headerImage = (string)$template->getData('header_image');
            $headerFormat = strtoupper((string)$template->getData('header_format'));

            if ($headerImage && $headerFormat === 'IMAGE') {
                if (!filter_var($headerImage, FILTER_VALIDATE_URL)) {
                    $headerImage = $this->storeManager->getStore()
                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . ltrim($headerImage, '/');
                }
            }

            return $result->setData([
                'variables' => $variables,
                'header_format' => $headerFormat ?: 'TEXT',
                'header_image' => $headerImage ?: null,
                'body' => (string)$template->getData('body')
            ]);
        } catch (\Exception $e) {
            return $result->setData(['variables' => [], 'error' => $e->getMessage()]);
        }
    }
}
