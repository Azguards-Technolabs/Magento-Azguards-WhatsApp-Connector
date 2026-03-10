<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\Exception\LocalizedException;

class Validator
{
    private const MAX_HEADER_LENGTH = 60;
    private const MAX_BODY_LENGTH = 1024;
    private const MAX_FOOTER_LENGTH = 60;
    private const MAX_BUTTON_TEXT_LENGTH = 25;
    private const MAX_TOTAL_BUTTONS = 10;
    private const MAX_URL_BUTTONS = 2;
    private const MAX_PHONE_BUTTONS = 1;
    private const MAX_COPY_CODE_EXAMPLE_LENGTH = 15;
    private const MAX_TTL_SECONDS = 604800; // 7 days
    private const MAX_OTP_TTL_SECONDS = 86400; // 24 hours

    public function validate(array $data): void
    {
        $this->validateBasicFields($data);
        $this->validateTTL($data);
        $this->validateComponents($data);
    }

    private function validateBasicFields(array $data): void
    {
        if (empty($data['name']) || !preg_match('/^[a-z0-9_]+$/', $data['name'])) {
            throw new LocalizedException(__('Invalid template name. Only lowercase alphanumeric and underscores are allowed.'));
        }

        if (empty($data['category'])) {
            throw new LocalizedException(__('Category is required.'));
        }

        if (empty($data['language'])) {
            throw new LocalizedException(__('Language is required.'));
        }

        $validTypes = ['IMAGE', 'TEXT', 'CAROUSEL', 'VIDEO', 'DOCUMENT', 'OTP', 'LTO'];
        if (empty($data['type']) || !in_array($data['type'], $validTypes)) {
            throw new LocalizedException(__('Invalid template type.'));
        }
    }

    private function validateTTL(array $data): void
    {
        if (isset($data['message_send_ttl_seconds'])) {
            $ttl = (int)$data['message_send_ttl_seconds'];
            if ($ttl <= 0 || $ttl > self::MAX_TTL_SECONDS) {
                throw new LocalizedException(__('TTL must be a positive integer <= 7 days.'));
            }

            if (($data['type'] ?? '') === 'OTP' && $ttl > self::MAX_OTP_TTL_SECONDS) {
                throw new LocalizedException(__('OTP TTL cannot exceed 24 hours.'));
            }
        }
    }

    private function validateComponents(array $data): void
    {
        $components = $data['components'] ?? [];
        $header = null;
        $body = null;
        $footer = null;
        $buttons = [];
        $carousel = null;

        foreach ($components as $component) {
            switch ($component['type']) {
                case 'HEADER':
                    $header = $component;
                    break;
                case 'BODY':
                    $body = $component;
                    break;
                case 'FOOTER':
                    $footer = $component;
                    break;
                case 'BUTTONS':
                    $buttons = $component['buttons'] ?? [];
                    break;
                case 'CAROUSEL':
                    $carousel = $component;
                    break;
            }
        }

        if ($header) $this->validateHeader($header);
        if ($body) $this->validateBody($body);
        if ($footer) $this->validateFooter($footer);
        if (!empty($buttons)) $this->validateButtons($buttons, $data['type'] ?? '');
        if ($carousel) $this->validateCarousel($carousel);
    }

    private function validateHeader(array $header): void
    {
        if (($header['format'] ?? '') === 'TEXT') {
            $text = $header['text'] ?? '';
            if (mb_strlen($text) > self::MAX_HEADER_LENGTH) {
                throw new LocalizedException(__('Header text exceeds %1 characters.', self::MAX_HEADER_LENGTH));
            }
            $this->validatePlaceholders($text, $header['example'] ?? null, 'Header');
        }
    }

    private function validateBody(array $body): void
    {
        $text = $body['text'] ?? '';
        if (mb_strlen($text) > self::MAX_BODY_LENGTH) {
            throw new LocalizedException(__('Body text exceeds %1 characters.', self::MAX_BODY_LENGTH));
        }
        $this->validatePlaceholders($text, $body['example'] ?? null, 'Body');
    }

    private function validateFooter(array $footer): void
    {
        $text = $footer['text'] ?? '';
        if (mb_strlen($text) > self::MAX_FOOTER_LENGTH) {
            throw new LocalizedException(__('Footer text exceeds %1 characters.', self::MAX_FOOTER_LENGTH));
        }
    }

    private function validateButtons(array $buttons, string $templateType): void
    {
        if (count($buttons) > self::MAX_TOTAL_BUTTONS) {
            throw new LocalizedException(__('Total buttons cannot exceed %1.', self::MAX_TOTAL_BUTTONS));
        }

        $urlCount = 0;
        $phoneCount = 0;
        $otpCount = 0;

        foreach ($buttons as $button) {
            $type = $button['type'] ?? '';
            $text = $button['text'] ?? '';

            if (mb_strlen($text) > self::MAX_BUTTON_TEXT_LENGTH) {
                throw new LocalizedException(__('Button text exceeds %1 characters.', self::MAX_BUTTON_TEXT_LENGTH));
            }

            switch ($type) {
                case 'URL':
                    $urlCount++;
                    if (!filter_var($button['url'] ?? '', FILTER_VALIDATE_URL)) {
                        throw new LocalizedException(__('Invalid URL in button.'));
                    }
                    $this->validatePlaceholders($button['url'] ?? '', $button['example'] ?? null, 'URL Button');
                    break;
                case 'PHONE_NUMBER':
                    $phoneCount++;
                    break;
                case 'COPY_CODE':
                    if (mb_strlen($button['example'] ?? '') > self::MAX_COPY_CODE_EXAMPLE_LENGTH) {
                        throw new LocalizedException(__('COPY_CODE example text exceeds %1 chars.', self::MAX_COPY_CODE_EXAMPLE_LENGTH));
                    }
                    break;
                case 'OTP':
                    $otpCount++;
                    break;
            }
        }

        if ($urlCount > self::MAX_URL_BUTTONS) {
            throw new LocalizedException(__('Max %1 URL buttons allowed.', self::MAX_URL_BUTTONS));
        }
        if ($phoneCount > self::MAX_PHONE_BUTTONS) {
            throw new LocalizedException(__('Max %1 PHONE_NUMBER button allowed.', self::MAX_PHONE_BUTTONS));
        }

        if ($templateType === 'LTO' && (count($buttons) < 1 || count($buttons) > 2)) {
            throw new LocalizedException(__('LTO templates must have 1-2 buttons.'));
        }

        if ($templateType === 'OTP') {
            if ($otpCount !== 1 || count($buttons) !== 1) {
                throw new LocalizedException(__('OTP templates must have exactly 1 button of type OTP.'));
            }
        }
    }

    private function validateCarousel(array $carousel): void
    {
        $cards = $carousel['cards'] ?? [];
        if (empty($cards)) {
            throw new LocalizedException(__('Carousel must contain at least one card.'));
        }
    }

    private function validatePlaceholders(string $text, ?array $example, string $location): void
    {
        preg_match_all('/{{(\d+)}}/', $text, $matches);
        $placeholders = array_map('intval', $matches[1]);

        if (empty($placeholders)) {
            return;
        }

        sort($placeholders);
        foreach ($placeholders as $index => $value) {
            if ($value !== ($index + 1)) {
                throw new LocalizedException(__('%1: Placeholders must be sequential starting from 1 (No {{2}} without {{1}}).', $location));
            }
        }

        $sampleValues = $example['header_text'] ?? $example['body_text'] ?? $example['url_text'] ?? [];
        if (count($sampleValues) < count($placeholders)) {
            throw new LocalizedException(__('%1: Sample values (examples) must be provided for each extracted variable.', $location));
        }

        foreach ($sampleValues as $val) {
            if (empty($val)) {
                throw new LocalizedException(__('%1: Sample values cannot be empty.', $location));
            }
        }
    }
}
