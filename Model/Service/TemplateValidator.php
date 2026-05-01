<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\Exception\LocalizedException;

class TemplateValidator
{
    /**
     * Validate the whole template data
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function validate(array $data): void
    {
        $this->validateTemplateName($data['template_name'] ?? '');
        $this->validateCharacterLimits($data);

        $body = $data['body'] ?? '';
        $this->validatePlaceholders($body);

        $templateType = $data['template_type'] ?? 'TEXT';

        if ($templateType === 'CAROUSEL') {
            $this->validateCarousel($data);
        } else {
            $this->validateButtons($data['buttons'] ?? [], $templateType);
            if ($templateType === 'OTP') {
                $this->validateSpecialTemplates($data, 'OTP');
            } elseif ($templateType === 'LTO') {
                $this->validateSpecialTemplates($data, 'LTO');
            } elseif ($templateType === 'COUPON_CODE') {
                $this->validateSpecialTemplates($data, 'COUPON_CODE');
            }
        }

        $headerFormat = $data['header_format'] ?? 'TEXT';
        if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $this->validateMediaHeader($data);
        }
    }

    /**
     * Validate template name format.
     *
     * @param string $name
     * @return void
     * @throws LocalizedException
     */
    public function validateTemplateName(string $name): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new LocalizedException(
                __('Template name must contain only lowercase letters, numbers, and underscores (e.g., order_update).')
            );
        }
    }

    /**
     * Validate sequential placeholders in text.
     *
     * @param string $text
     * @return void
     * @throws LocalizedException
     */
    public function validatePlaceholders(string $text): void
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        if (!empty($matches[1])) {
            $placeholders = array_map('intval', $matches[1]);
            sort($placeholders);

            // Check if sequential starting from 1
            $expected = 1;
            foreach ($placeholders as $placeholder) {
                if ($placeholder !== $expected) {
                    throw new LocalizedException(
                        __(
                            'Placeholders must be sequential and start with {{1}}. Found: {{%1}} but expected {{%2}}',
                            $placeholder,
                            $expected
                        )
                    );
                }
                $expected++;
            }
        }
    }

    /**
     * Validate template buttons.
     *
     * @param array $buttons
     * @param string $templateType
     * @return void
     * @throws LocalizedException
     */
    public function validateButtons(array $buttons, string $templateType = 'TEXT'): void
    {
        if (count($buttons) > 10) {
            throw new LocalizedException(__('Maximum 10 buttons are allowed.'));
        }

        $urlCount = 0;
        $phoneCount = 0;

        foreach ($buttons as $button) {
            $type = $button['type'] ?? '';
            $text = $button['text'] ?? '';

            if ($type !== 'COPY_CODE') {
                if (mb_strlen($text) > 25) {
                    throw new LocalizedException(
                        __('Button text cannot exceed 25 characters. Found: "%1"', $text)
                    );
                }
            }

            if ($type === 'URL') {
                $urlCount++;
            } elseif ($type === 'PHONE_NUMBER') {
                $phoneCount++;
            }
        }

        if ($urlCount > 2) {
            throw new LocalizedException(__('Maximum 2 URL buttons are allowed.'));
        }
        if ($phoneCount > 1) {
            throw new LocalizedException(__('Maximum 1 Phone Number button is allowed.'));
        }
    }

    /**
     * Validate carousel template payload.
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function validateCarousel(array $data): void
    {
        $cardsStr = $data['carousel_cards'] ?? '[]';
        $cards = is_string($cardsStr) ? json_decode($cardsStr, true) : $cardsStr;

        if (!is_array($cards)) {
            $cards = [];
        }

        if (count($cards) > 10) {
            throw new LocalizedException(__('Carousel can contain a maximum of 10 cards.'));
        }

        if (empty($cards)) {
            throw new LocalizedException(__('Carousel must contain at least 1 card.'));
        }

        foreach ($cards as $index => $card) {
            if (empty($card['body'])) {
                throw new LocalizedException(__('Card %1 must contain a body.', $index + 1));
            }
            if (!empty($card['body'])) {
                $this->validatePlaceholders($card['body']);
            }
            if (!empty($card['buttons'])) {
                $this->validateButtons($card['buttons']);
            }

            $headerFormat = $card['header_format'] ?? 'TEXT';
            if (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                if (empty($card['header_handle'])) {
                    throw new LocalizedException(
                        __('Media is required for Card %1 when header format is %2.', $index + 1, $headerFormat)
                    );
                }
            }
        }
    }

    /**
     * Validate special template types such as OTP, LTO, and coupon-code templates.
     *
     * @param array $data
     * @param string $type
     * @return void
     * @throws LocalizedException
     */
    public function validateSpecialTemplates(array $data, string $type): void
    {
        if ($type === 'OTP') {
            if (empty($data['footer'])) {
                throw new LocalizedException(__('OTP templates must contain a FOOTER.'));
            }

            $buttons = $data['buttons'] ?? [];
            if (is_string($buttons)) {
                $buttons = json_decode($buttons, true);
            }

            if (!is_array($buttons) || count($buttons) !== 1) {
                throw new LocalizedException(__('OTP templates must have exactly 1 button.'));
            }

            $button = $buttons[0];
            if (($button['type'] ?? '') !== 'OTP') {
                throw new LocalizedException(__('OTP template button type must be OTP.'));
            }

            if (empty($button['otp_type'])) {
                throw new LocalizedException(__('OTP button requires an otp_type (e.g., COPY_CODE, ZERO_TAP).'));
            }
        } elseif ($type === 'LTO') {
            $buttons = $data['buttons'] ?? [];
            if (is_string($buttons)) {
                $buttons = json_decode($buttons, true);
            }

            if (!is_array($buttons) || count($buttons) < 1 || count($buttons) > 2) {
                throw new LocalizedException(__('Limited Time Offer templates require 1 or 2 buttons.'));
            }

            foreach ($buttons as $button) {
                if (!in_array($button['type'] ?? '', ['COPY_CODE', 'URL'])) {
                    throw new LocalizedException(__('LTO buttons must be COPY_CODE or URL.'));
                }
            }

            $ltoData = $data['limited_time_offer'] ?? null;
            if (is_string($ltoData)) {
                $ltoData = json_decode($ltoData, true);
            }
            if (empty($ltoData) || empty($ltoData['text']) || empty($ltoData['expiration_minutes'])) {
                throw new LocalizedException(
                    __('LTO templates must include limited_time_offer object with text and expiration_minutes.')
                );
            }
        } elseif ($type === 'COUPON_CODE') {
            $category = strtoupper((string)($data['template_category'] ?? ''));
            if ($category !== 'MARKETING') {
                throw new LocalizedException(__('Coupon Code templates must use MARKETING category.'));
            }

            $buttons = $data['buttons'] ?? [];
            if (is_string($buttons)) {
                $buttons = json_decode($buttons, true);
            }

            if (!is_array($buttons) || $buttons === []) {
                throw new LocalizedException(__('Coupon Code templates must include at least 1 button (COPY_CODE).'));
            }

            $copyCount = 0;
            foreach ($buttons as $button) {
                if (($button['type'] ?? '') === 'COPY_CODE') {
                    $copyCount++;
                }
            }

            if ($copyCount !== 1) {
                throw new LocalizedException(__('Coupon Code templates must include exactly 1 COPY_CODE button.'));
            }
        }
    }

    /**
     * Validate header, body, and footer character limits.
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function validateCharacterLimits(array $data): void
    {
        $header = $data['header'] ?? '';
        $headerFormat = $data['header_format'] ?? 'TEXT';

        if ($headerFormat === 'TEXT' && mb_strlen($header) > 60) {
            throw new LocalizedException(__('Header text cannot exceed 60 characters.'));
        }

        $body = $data['body'] ?? '';
        if (mb_strlen($body) > 1024) {
            throw new LocalizedException(__('Body text cannot exceed 1024 characters.'));
        }

        $footer = $data['footer'] ?? '';
        if (mb_strlen($footer) > 60) {
            throw new LocalizedException(__('Footer text cannot exceed 60 characters.'));
        }
    }

    /**
     * Validate required media header data.
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function validateMediaHeader(array $data): void
    {
        $headerFormat = $data['header_format'] ?? '';
        $headerHandle = $data['header_handle'] ?? '';

        if (empty($headerHandle)) {
            throw new LocalizedException(
                __('Header media is required for %1 format. Media handle missing.', $headerFormat)
            );
        }
    }
}
