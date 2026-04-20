<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Psr\Log\LoggerInterface;

class MetaTemplatePayloadBuilder
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Build the payload for the Meta API
     *
     * @param TemplateInterface $template
     * @return array
     */
    public function build(TemplateInterface $template): array
    {
        $templateType = $template->getTemplateType() ?: 'TEXT';

        // Robustly map generic MEDIA type to specific Meta format (IMAGE, VIDEO, or DOCUMENT)
        if ($templateType === 'MEDIA') {
            $format = strtoupper((string)$template->getHeaderFormat());
            $templateType = in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true) ? $format : 'TEXT';
        }

        $payload = [
            'name'     => $template->getTemplateName(),
            'language' => $template->getLanguage(),
            'category' => strtoupper((string)$template->getTemplateCategory()),
            'type'     => $templateType
        ];

        if ($template->getTemplateType() === 'CAROUSEL') {
            $payload['carouselFormat'] = $this->resolveCarouselFormat($template);
            $payload['carousel']       = $this->buildCarouselCards($template);
            $body = $this->buildBody($template);
            if ($body) {
                $payload['body'] = $body;
            }
        } else {
            $header = $this->buildHeader($template);
            if ($header) {
                $payload['header'] = $header;
            }

            $body = $this->buildBody($template);
            if ($body) {
                $payload['body'] = $body;
            }

            $footer = $this->buildFooter($template);
            if ($footer) {
                $payload['footer'] = $footer;
            }

            $buttons = $this->buildButtons($template->getButtons());
            if (!empty($buttons)) {
                $payload['buttons'] = $buttons;
            }
        }

        return $payload;
    }

    protected function buildHeader(TemplateInterface $template): ?array
    {
        $format = $template->getHeaderFormat() ?: 'TEXT';

        if ($format === 'TEXT' && $template->getHeader()) {
            $headerText = $template->getHeader();
            $result     = $this->processTextVariables($headerText);

            $header = [
                'type'   => 'HEADER',
                'format' => 'TEXT',
                'text'   => $result['text']
            ];

            if (!empty($result['params'])) {
                $header['param'] = $result['params'];
            }

            return $header;
        }

        if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $documentId = $template->getHeaderHandle();
            if ($documentId) {
                $this->logger->info('Payload Builder: Building media header payload', [
                    'template_name' => $template->getTemplateName(),
                    'format'        => $format,
                    'document_id'   => $documentId
                ]);
                return [
                    'type'   => 'HEADER',
                    'format' => $format,
                    'media'  => [
                        'document_id' => $documentId
                    ]
                ];
            }
        }

        return null;
    }

    protected function buildBody(TemplateInterface $template): ?array
    {
        $text = $template->getBody();
        if ($text) {
            $result = $this->processTextVariables($text);

            $body = [
                'type'   => 'BODY',
                'format' => 'TEXT',
                'text'   => $result['text']
            ];

            $examplesStr = $template->getData('body_examples_json');
            $examples    = $examplesStr ? json_decode($examplesStr, true) : [];

            if (!empty($examples)) {
                $body['example'] = [
                    'body_text' => [$examples]
                ];
            } elseif (!empty($result['params'])) {
                $body['example'] = [
                    'body_text' => [$result['params']]
                ];
            }

            return $body;
        }

        return null;
    }

    protected function buildFooter(TemplateInterface $template): ?array
    {
        $text = $template->getFooter();
        if ($text) {
            return [
                'type' => 'footer', // Senior Fix: Use lowercase for specific ERP API alignment
                'text' => $text
            ];
        }
        return null;
    }

    protected function buildButtons(?string $buttonsJson): array
    {
        if (!$buttonsJson) {
            return [];
        }

        $buttonsData = json_decode($buttonsJson, true);
        if (!is_array($buttonsData)) {
            return [];
        }

        if (isset($buttonsData['type'])) {
            $buttonsData = [$buttonsData];
        }

        $buttons = [];
        foreach ($buttonsData as $btn) {
            if (!is_array($btn) || empty($btn['type'])) {
                continue;
            }

            $type   = strtoupper((string)$btn['type']);
            $button = ['type' => $type];

            switch ($type) {
                case 'URL':
                    $button['text'] = $btn['text'] ?? '';
                    $urlResult      = $this->processTextVariables((string)($btn['url'] ?? ($btn['value'] ?? '')));
                    $button['url']  = $urlResult['text'];
                    if (!empty($urlResult['params'])) {
                        $button['example'] = $urlResult['params'];
                    }
                    break;

                case 'PHONE_NUMBER':
                    $button['text']  = $btn['text'] ?? '';
                    $button['value'] = $btn['phone_number'] ?? ($btn['value'] ?? '');
                    break;

                case 'QUICK_REPLY':
                    $button['text'] = $btn['text'] ?? '';
                    break;

                case 'OTP':
                    $button['otp_type'] = $btn['otp_type'] ?? '';
                    if (!empty($btn['text'])) {
                        $button['text'] = $btn['text'];
                    }
                    break;

                case 'COPY_CODE':
                    /**
                     * Senior Decision: The ERP API expects the actual code in the 'text' field 
                     * for COPY_CODE buttons in a flat structure.
                     */
                    $couponCode = trim((string)($btn['coupon_code'] ?? ($btn['value'] ?? '')));
                    if ($couponCode !== '') {
                        if (mb_strlen($couponCode) > 15) {
                            $couponCode = mb_substr($couponCode, 0, 15);
                        }
                        $button['text'] = $couponCode;
                    } else {
                        $button['text'] = $btn['text'] ?? '';
                    }
                    // Do NOT include coupon_code key at top level if not requested
                    break;
            }

            $buttons[] = $button;
        }

        return $buttons;
    }

    protected function buildCarouselCards(TemplateInterface $template): array
    {
        $cardsStr  = $template->getCarouselCards();
        $cardsData = $cardsStr ? json_decode($cardsStr, true) : [];
        $cards     = [];

        foreach ($cardsData as $cardData) {
            $card = [];

            // Header (Media)
            $headerFormat = strtoupper((string)($cardData['header_format'] ?? ''));
            if (in_array($headerFormat, ['IMAGE', 'VIDEO'], true) && !empty($cardData['header_handle'])) {
                $card['header'] = [
                    'type'   => 'HEADER',
                    'format' => $headerFormat,
                    'media'  => [
                        'document_id' => $cardData['header_handle']
                    ]
                ];
            }

            // Body
            if (!empty($cardData['body'])) {
                $bodyResult   = $this->processTextVariables((string)$cardData['body']);
                $card['body'] = [
                    'type'   => 'BODY',
                    'format' => 'TEXT',
                    'text'   => $bodyResult['text']
                ];
                if (!empty($bodyResult['params'])) {
                    $card['body']['example'] = [
                        'body_text' => [$bodyResult['params']]
                    ];
                }
            }

            // Buttons
            $cardButtons = $cardData['buttons'] ?? ($cardData['buttons_json'] ?? null);
            if (!empty($cardButtons)) {
                $buttons = $this->buildButtons(is_string($cardButtons) ? $cardButtons : json_encode($cardButtons));
                if (!empty($buttons)) {
                    $card['buttons'] = $buttons;
                }
            }

            $cards[] = $card;
        }

        return $cards;
    }

    protected function resolveCarouselFormat(TemplateInterface $template): string
    {
        $storedFormat = strtoupper((string)$template->getCarouselFormat());
        if (in_array($storedFormat, ['IMAGE', 'VIDEO'], true)) {
            return $storedFormat;
        }

        $cardsData = json_decode((string)$template->getCarouselCards(), true);
        if (is_array($cardsData)) {
            foreach ($cardsData as $cardData) {
                $cardFormat = strtoupper((string)($cardData['header_format'] ?? ''));
                if (in_array($cardFormat, ['IMAGE', 'VIDEO'], true)) {
                    return $cardFormat;
                }
            }
        }

        return 'IMAGE';
    }

    /**
     * Process text to transform named variables to numeric and extract params.
     *
     * Example: "Hello {{name}}" -> ["text" => "Hello {{1}}", "params" => ["name"]]
     */
    protected function processTextVariables(string $text): array
    {
        $params = [];
        $variableMap = []; // To track unique variables and their assigned indices
        $counter = 0;

        $transformedText = preg_replace_callback(
            '/\{\{(.*?)\}\}/',
            function ($matches) use (&$params, &$variableMap, &$counter) {
                $originalVar = trim($matches[1]);
                
                // If we've already seen this variable, reuse its index
                if (isset($variableMap[$originalVar])) {
                    $index = $variableMap[$originalVar];
                } else {
                    $counter++;
                    $index = $counter;
                    $variableMap[$originalVar] = $index;
                    
                    // Determine what to use for the example value
                    if (is_numeric($originalVar)) {
                        $params[] = (string)$originalVar; // Use the number itself (e.g. 1)
                    } else {
                        $params[] = $originalVar; // Use the original name (e.g. name, order_id)
                    }
                }
                
                return '{{' . $index . '}}';
            },
            $text
        );

        return [
            'text'   => $transformedText,
            'params' => $params
        ];
    }
}
