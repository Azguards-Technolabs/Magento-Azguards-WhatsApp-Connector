<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Escaper;
use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Azguards\WhatsAppConnect\Model\Service\MediaDocumentService;
use Azguards\WhatsAppConnect\Model\Service\MediaResolver;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableExtractor;
use Azguards\WhatsAppConnect\Model\Service\TemplateVariableRowsBuilder;
use Azguards\WhatsAppConnect\Model\Service\VariableOptionsProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Preview extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private TemplateRepositoryInterface $templateRepository;
    private MediaDocumentService $mediaDocumentService;
    private MediaResolver $mediaResolver;
    private StoreManagerInterface $storeManager;
    private Escaper $escaper;
    private ScopeConfigInterface $scopeConfig;
    private EventConfig $eventConfig;
    private TemplateVariableExtractor $variableExtractor;
    private VariableOptionsProvider $variableOptionsProvider;
    private TemplateVariableRowsBuilder $variableRowsBuilder;
    private array $variableLabelMap = [];

    /**
     * Preview constructor
     *
     * @param Context $context
     * @param TemplateRepositoryInterface $templateRepository
     * @param MediaDocumentService $mediaDocumentService
     * @param MediaResolver $mediaResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param EventConfig $eventConfig
     * @param TemplateVariableExtractor $variableExtractor
     * @param VariableOptionsProvider $variableOptionsProvider
     * @param TemplateVariableRowsBuilder $variableRowsBuilder
     * @param StoreManagerInterface $storeManager
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        TemplateRepositoryInterface $templateRepository,
        MediaDocumentService $mediaDocumentService,
        MediaResolver $mediaResolver,
        ScopeConfigInterface $scopeConfig,
        EventConfig $eventConfig,
        TemplateVariableExtractor $variableExtractor,
        VariableOptionsProvider $variableOptionsProvider,
        TemplateVariableRowsBuilder $variableRowsBuilder,
        StoreManagerInterface $storeManager,
        Escaper $escaper
    ) {
        parent::__construct($context);
        $this->templateRepository = $templateRepository;
        $this->mediaDocumentService = $mediaDocumentService;
        $this->mediaResolver = $mediaResolver;
        $this->scopeConfig = $scopeConfig;
        $this->eventConfig = $eventConfig;
        $this->variableExtractor = $variableExtractor;
        $this->variableOptionsProvider = $variableOptionsProvider;
        $this->variableRowsBuilder = $variableRowsBuilder;
        $this->storeManager = $storeManager;
        $this->escaper = $escaper;
    }

    /**
     * Generate template preview HTML
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('This template no longer exists.'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $template = $this->templateRepository->getById((int)$id);
            $html = $this->renderPreview($template);

            /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
            $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $resultRaw->setContents($html);
            return $resultRaw;

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
    }

    private function renderPreview($template): string
    {
        $this->variableLabelMap = $this->buildVariableLabelMap($template);
        $templateType = strtoupper((string)$template->getTemplateType());
        $html = "<div style='background:#e5ddd5;padding:20px;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;min-height:100vh;box-sizing:border-box;'>";
        $html .= "<h3 style='margin:0;'>WhatsApp Template Preview</h3>";
        $html .= "<hr style='border:0;border-top:1px solid #ccc;margin:15px 0;' />";

        if ($templateType === 'CAROUSEL') {
            $html .= $this->renderCarouselPreview($template);
        } else {
            $html .= $this->renderSingleMessagePreview(
                $template->getHeaderFormat(),
                $template->getHeader(),
                $template->getHeaderImage(),
                $template->getHeaderHandle(),
                $template->getBody(),
                $template->getFooter(),
                $this->decodeJson((string)$template->getButtons())
            );
        }

        $html .= "</div>";

        return $html;
    }

    private function buildVariableLabelMap($template): array
    {
        $labels = [];

        $examples = [];
        $examplesJson = (string)$template->getData('body_examples_json');
        if ($examplesJson !== '') {
            $decoded = json_decode($examplesJson, true);
            if (is_array($decoded)) {
                $examples = $decoded;
            }
        }

        $vars = $this->variableExtractor->extractFromTemplate($template);
        $hasNumeric = false;
        foreach ($vars as $var) {
            if (is_numeric($var)) {
                $hasNumeric = true;
                break;
            }
        }

        // If template contains numeric placeholders, map them to API variable names by position (1->name, 2->order_id, etc).
        $positionToName = [];
        if ($hasNumeric) {
            $rows = $this->variableRowsBuilder->buildByExternalTemplateId((string)$template->getTemplateId());
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $pos = (string)($row['order'] ?? '');
                $name = (string)($row['type'] ?? $row['title'] ?? '');
                if ($pos !== '' && $name !== '') {
                    $positionToName[$pos] = $name;
                }
            }
        }

        foreach ($vars as $var) {
            if (is_numeric($var) && isset($positionToName[$var])) {
                $labels[$var] = $positionToName[$var];
                continue;
            }

            $labels[$var] = $var;
            if (is_numeric($var)) {
                $idx = (int)$var - 1;
                if (isset($examples[$idx]) && is_scalar($examples[$idx]) && (string)$examples[$idx] !== '') {
                    $labels[$var] = (string)$examples[$idx];
                }
            }
        }

        // If this template is configured for an event, prefer configured labels.
        foreach ($this->getKnownEventCodes() as $eventCode) {
            $cfg = $this->eventConfig->get($eventCode);
            if ($cfg === [] || empty($cfg['template']) || empty($cfg['variables'])) {
                continue;
            }

            $configuredTemplateId = (string)$this->scopeConfig->getValue((string)$cfg['template']);
            if ($configuredTemplateId === '' || $configuredTemplateId !== (string)$template->getTemplateId()) {
                continue;
            }

            $rawMap = (string)$this->scopeConfig->getValue((string)$cfg['variables']);
            $decodedMap = $rawMap !== '' ? json_decode($rawMap, true) : null;
            if (!is_array($decodedMap)) {
                continue;
            }

            $options = $this->variableOptionsProvider->getForEvent($eventCode);
            foreach ($decodedMap as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $varName = (string)($row['type'] ?? $row['title'] ?? '');
                $source = trim((string)($row['limit'] ?? ''));
                if ($varName === '' || $source === '') {
                    continue;
                }
                $labels[$varName] = $options[$source] ?? $source;
            }

            break; // First matching event is enough for preview context.
        }

        return $labels;
    }

    private function getKnownEventCodes(): array
    {
        return [
            EventConfig::CUSTOMER_REGISTRATION,
            EventConfig::ORDER_CREATION,
            EventConfig::ORDER_INVOICE,
            EventConfig::ORDER_SHIPMENT,
            EventConfig::ORDER_CANCELLATION,
            EventConfig::ORDER_CREDIT_MEMO,
        ];
    }

    private function renderCarouselPreview($template): string
    {
        $cards = $this->decodeJson((string)$template->getCarouselCards());
        $html = "<div style='max-width:760px;'>";

        if ($template->getBody()) {
            $html .= $this->renderSingleMessagePreview(
                null,
                null,
                null,
                null,
                $template->getBody(),
                $template->getFooter(),
                []
            );
        }

        if (empty($cards)) {
            $html .= "<div style='max-width:320px;background:#fff;padding:14px;border-radius:8px;box-shadow:0 1px 0.5px rgba(0,0,0,0.13);color:#667781;'>No carousel cards available.</div>";
            return $html . "</div>";
        }

        $html .= "<div style='display:flex;gap:14px;overflow-x:auto;padding:6px 2px 2px;'>";
        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $html .= "<div style='width:280px;min-width:280px;background:#fff;border-radius:8px;box-shadow:0 1px 0.5px rgba(0,0,0,0.13);overflow:hidden;'>";
            $html .= $this->renderHeaderSection(
                $card['header_format'] ?? $template->getCarouselFormat(),
                $card['header'] ?? null,
                $this->getFullImageUrl($card['header_image'] ?? null),
                $card['header_handle'] ?? null,
                true
            );
            $html .= "<div style='padding:10px;'>";
            $html .= $this->renderBodySection((string)($card['body'] ?? ''));

            if (!empty($card['footer'])) {
                $html .= "<div style='color:#667781;font-size:12px;margin-top:8px;'>" . $this->escape($card['footer']) . "</div>";
            }

            $html .= "</div>";
            $html .= $this->renderButtonsSection($this->normalizeButtons($card['buttons'] ?? ($card['buttons_json'] ?? [])));
            $html .= "</div>";
        }
        $html .= "</div></div>";

        return $html;
    }

    private function renderSingleMessagePreview(
        ?string $headerFormat,
        ?string $headerText,
        ?string $headerImage,
        ?string $headerHandle,
        ?string $body,
        ?string $footer,
        array $buttons
    ): string {
        $html = "<div style='max-width:320px;background:#fff;padding:10px;border-radius:8px;box-shadow:0 1px 0.5px rgba(0,0,0,0.13);position:relative;overflow:hidden;'>";
        $html .= $this->renderHeaderSection($headerFormat, $headerText, $headerImage, $headerHandle, false);
        $html .= $this->renderBodySection((string)$body);

        if (!empty($footer)) {
            $html .= "<div style='color:#667781;font-size:12px;margin-top:8px;'>" . $this->escape($footer) . "</div>";
        }

        $html .= $this->renderButtonsSection($this->normalizeButtons($buttons));
        $html .= "</div>";

        return $html;
    }

    private function renderHeaderSection(
        ?string $headerFormat,
        ?string $headerText,
        ?string $headerImage,
        ?string $headerHandle,
        bool $isCard
    ): string {
        $format = strtoupper((string)$headerFormat);
        $spacing = $isCard ? '' : "margin:-10px -10px 10px -10px;";

        // Senior Logic: If format is TEXT but header contains JSON media data, treat as IMAGE for preview.
        if ($format === 'TEXT' && $headerText && str_starts_with(trim($headerText), '{')) {
            $format = 'IMAGE';
            $headerHandle = $headerText; // Use the JSON as the handle for resolution
        }

        if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $mediaUrl = $this->resolveMediaUrl($headerImage, $headerHandle);
            if ($mediaUrl !== null) {
                if ($format === 'IMAGE') {
                    return "<div style='{$spacing}'><img src='" . $this->escapeAttr($mediaUrl) . "' alt='Template media' style='width:100%;height:190px;object-fit:cover;display:block;" . ($isCard ? '' : "border-radius:7.5px 7.5px 0 0;") . "' /></div>";
                }

                return "<div style='background:#dfe5e7;height:150px;display:flex;align-items:center;justify-content:center;color:#54656f;font-size:13px;text-align:center;padding:0 20px;{$spacing}'>" .
                    $this->escape($format . ' attached') .
                    "</div>";
            }

            $identifier = $headerHandle ?: $headerImage ?: 'Unavailable';
            
            // Senior UI: Don't show raw JSON as a fallback identifier
            $displayIdentifier = ($identifier && str_starts_with(trim((string)$identifier), '{')) ? 'Media Handle' : $identifier;

            return "<div style='background:#fde8e8;height:150px;display:flex;align-items:center;justify-content:center;color:#9b1c1c;font-size:13px;text-align:center;padding:0 20px;{$spacing}'>" .
                $this->escape($format . ' preview unavailable') .
                "<br /><small>" . $this->escape($displayIdentifier) . "</small></div>";
        }

        if (!empty($headerText)) {
            // Senior Fix: Don't show JSON content as text, especially if it looks like a media handle
            if (trim($headerText) !== '' && str_starts_with(trim($headerText), '{')) {
                $decoded = json_decode($headerText, true);
                if (is_array($decoded)) {
                    return ''; // Suppress JSON in header preview
                }
            }
            return "<div style='font-weight:700;font-size:16px;margin-bottom:6px;color:#111b21;'>" . $this->formatText($headerText, '[Header Variable]') . "</div>";
        }

        return '';
    }

    private function renderBodySection(string $body): string
    {
        if ($body === '') {
            return '';
        }

        return "<div style='font-size:14.2px;line-height:1.4;color:#111b21;white-space:pre-wrap;'>" .
            $this->formatText($body, '[Variable]') .
            "</div>";
    }

    private function renderButtonsSection(array $buttons): string
    {
        if (empty($buttons)) {
            return '';
        }

        $html = "<div style='border-top:1px solid #f0f2f5;background:#fff;'>";
        foreach ($buttons as $button) {
            $type = strtoupper((string)($button['type'] ?? ''));
            $label = trim((string)($button['text'] ?? $button['label'] ?? $type));
            if ($label === '') {
                $label = 'Action';
            }

            $prefix = match ($type) {
                'URL' => '[URL] ',
                'PHONE', 'PHONE_NUMBER' => '[Call] ',
                'QUICK_REPLY' => '[Reply] ',
                'OTP' => '[OTP] ',
                default => ''
            };

            $html .= "<div style='text-align:center;padding:10px;border-bottom:1px solid #f0f2f5;color:#00a884;font-weight:500;font-size:14px;'>" .
                $this->escape($prefix . $label) .
                "</div>";
        }
        $html .= "</div>";

        return $html;
    }

    private function resolveMediaUrl(?string $previewValue, ?string $documentId): ?string
    {
        // 1. If we already have a local path, resolve and return it immediately
        if ($previewValue && !filter_var($previewValue, FILTER_VALIDATE_URL)) {
            return $this->getFullImageUrl($previewValue);
        }

        // 2. If it's a URL, it might be an expired S3 link. 
        // If we have a documentId, try to resolve a fresh link from API first.
        if ($documentId) {
            $documentId = $this->mediaResolver->resolveHandler($documentId);
            if ($documentId) {
                try {
                    $resolved = $this->mediaDocumentService->getPreviewLink($documentId, false);
                    if ($resolved && filter_var($resolved, FILTER_VALIDATE_URL)) {
                        return $resolved;
                    }
                } catch (\Throwable $e) {
                    // Fallback to whatever we have
                }
            }
        }

        // 3. Fallback to the original URL if we have it
        if ($previewValue && filter_var($previewValue, FILTER_VALIDATE_URL)) {
            return $previewValue;
        }

        return null;
    }

    /**
     * Get full image URL from potentially local path.
     */
    private function getFullImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        try {
            return rtrim($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/') . '/' . ltrim($path, '/');
        } catch (\Exception $e) {
            return $path;
        }
    }

    private function normalizeButtons($buttons): array
    {
        if (is_string($buttons)) {
            $buttons = $this->decodeJson($buttons);
        }

        if (!is_array($buttons)) {
            return [];
        }

        if (isset($buttons['type'])) {
            return [$buttons];
        }

        return array_values(array_filter($buttons, 'is_array'));
    }

    private function decodeJson(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function formatText(string $text, string $replacementLabel): string
    {
        $escaped = nl2br($this->escape($text));
        $map = $this->variableLabelMap;

        return (string)preg_replace_callback('/\{\{\s*([^}]+?)\s*\}\}/', function (array $m) use ($map, $replacementLabel) {
            $name = trim((string)($m[1] ?? ''));
            $label = $name !== '' && isset($map[$name]) ? (string)$map[$name] : $replacementLabel;
            return '<b style="color:#00a884;">' . $this->escape($label) . '</b>';
        }, $escaped);
    }

    private function escape(?string $value): string
    {
        return $this->escaper->escapeHtml((string)$value);
    }

    private function escapeAttr(?string $value): string
    {
        return $this->escaper->escapeHtmlAttr((string)$value);
    }
}
