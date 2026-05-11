<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Model\Api\MetaLibraryApi;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class MetaLibraryTemplateService
{
    private const XML_PATH_PROJECT_NAME = 'whatsApp_conector/general/project_name';

    /**
     * @var MetaLibraryApi
     */
    private $metaLibraryApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param MetaLibraryApi $metaLibraryApi
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        MetaLibraryApi $metaLibraryApi,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->metaLibraryApi = $metaLibraryApi;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Fetch and map template from Meta Library for a given event code
     *
     * @param string $eventCode
     * @param string $language
     * @return array
     */
    public function getMappedTemplate(string $eventCode, string $language = 'en_US'): array
    {
        $templateName = $this->getTemplateNameByEvent($eventCode);
        if (!$templateName) {
            return [
                'success' => false,
                'message' => __('No library template mapping found for event: %1', $eventCode)
            ];
        }

        try {
            $response = $this->metaLibraryApi->fetchTemplate($templateName, $language);
            $data = $response['data'][0] ?? null;

            if (!$data) {
                return [
                    'success' => false,
                    'message' => __('Template %1 not found in Meta Library for language %2', $templateName, $language)
                ];
            }

            return [
                'success' => true,
                'data' => $this->mapMetaTemplateToMagento($eventCode, $data)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map Magento event code to Meta Library template name
     *
     * @param string $eventCode
     * @return string|null
     */
    private function getTemplateNameByEvent(string $eventCode): ?string
    {
        $map = [
            'order_created'     => 'order_management_2',
            'order_shipment'    => 'shipment_confirmation_5',
            'order_cancellation' => 'order_canceled_1',
            'order_credit_memo' => 'refund_confirmation_1',
            'order_invoice'     => 'delivery_confirmation_2',
            'abandon_cart'      => 'order_management_2'
        ];

        return $map[$eventCode] ?? null;
    }

    /**
     * Map Meta template structure to Magento builder format
     *
     * @param string $eventCode
     * @param array $metaData
     * @return array
     */
    private function mapMetaTemplateToMagento(string $eventCode, array $metaData): array
    {
        $body = (string)($metaData['body'] ?? '');
        $body = $this->resolveTemplateParameters($eventCode, $body);

        $headerText = (string)($metaData['header'] ?? '');
        $headerText = $this->resolveTemplateParameters($eventCode, $headerText);

        $buttons = [];
        if (isset($metaData['buttons']) && is_array($metaData['buttons'])) {
            foreach ($metaData['buttons'] as $btn) {
                $mappedBtn = [
                    'type' => strtoupper((string)($btn['type'] ?? 'QUICK_REPLY')),
                    'text' => (string)($btn['text'] ?? '')
                ];

                if ($mappedBtn['type'] === 'URL') {
                    $mappedBtn['button_url'] = (string)($btn['url'] ?? '');
                } elseif ($mappedBtn['type'] === 'PHONE_NUMBER') {
                    $mappedBtn['phone_number'] = (string)($btn['phone_number'] ?? '');
                }

                $buttons[] = $mappedBtn;
            }
        }

        $templateName = (string)($metaData['name'] ?? '');
        $projectSuffix = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_PROJECT_NAME,
            ScopeInterface::SCOPE_STORE
        ));

        if ($projectSuffix !== '') {
            $templateName .= '_' . preg_replace('/[^a-z0-9_]/', '', strtolower($projectSuffix));
            $templateName = substr($templateName, 0, 50);
        }

        return [
            'header_type' => $headerText !== '' ? 'text' : 'none',
            'header_text' => $headerText,
            'body_template' => $body,
            'footer_template' => (string)($metaData['footer'] ?? ''),
            'buttons_json' => json_encode($buttons),
            'category' => (string)($metaData['category'] ?? 'UTILITY'),
            'template_name' => $templateName
        ];
    }

    /**
     * Resolve {{1}}, {{text}}, etc. based on event code
     *
     * @param string $eventCode
     * @param string $text
     * @return string
     */
    private function resolveTemplateParameters(string $eventCode, string $text): string
    {
        if ($text === '') {
            return '';
        }

        $variableMap = $this->getVariableMapByEvent($eventCode);

        return preg_replace_callback('/\{\{([a-zA-Z0-9_]+)\}\}/', function ($matches) use ($variableMap) {
            $key = $matches[1];
            return $variableMap[$key] ?? $matches[0];
        }, $text);
    }

    /**
     * Get variable map for template parameters (positional and named) by event
     *
     * @param string $eventCode
     * @return array
     */
    private function getVariableMapByEvent(string $eventCode): array
    {
        $orderFirstname = '{{var order.customer_firstname}}';
        $orderId = '{{var order.increment_id}}';
        $createdAt = '{{var order.created_at}}';
        $total = '{{var order.grand_total}}';

        $quoteFirstname = '{{var quote.customer_firstname}}';
        $quoteTotal = '{{var quote.grand_total}}';

        $maps = [
            'order_created' => [
                '1' => $orderFirstname,
                '2' => $orderId,
                '3' => $createdAt
            ],
            'order_credit_memo' => [
                '1' => $orderFirstname,
                '2' => $total,
                '3' => $orderId
            ],
            'order_invoice' => [ // delivery_confirmation_2 uses {{text}} and {{order}}
                '1' => $orderFirstname,
                '2' => $orderId,
                'text' => $orderFirstname,
                'order' => $orderId
            ],
            'order_shipment' => [
                '1' => $orderFirstname,
                '2' => $orderId
            ],
            'order_cancellation' => [
                '1' => $orderFirstname,
                '2' => $orderId,
                '3' => '7'
            ],
            'abandon_cart' => [
                '1' => $quoteFirstname,
                '2' => $quoteTotal,
                '3' => ''
            ]
        ];

        return $maps[$eventCode] ?? [
            '1' => $orderFirstname,
            '2' => $orderId,
            '3' => $createdAt,
            'text' => $orderFirstname,
            'order' => $orderId
        ];
    }
}
