<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Logger\Logger;

class WhatsAppEventLogger
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function logEventTriggered(string $eventCode, array $context = []): void
    {
        $this->logger->info($this->format('event_triggered', $eventCode, $context));
    }

    public function logPayload(string $eventCode, array $payload, array $meta = []): void
    {
        $this->logger->info($this->format('payload', $eventCode, [
            'payload' => $payload,
            'meta' => $meta,
        ]));
    }

    public function logApiResponse(string $eventCode, array $response, array $meta = []): void
    {
        $this->logger->info($this->format('api_response', $eventCode, [
            'response' => $response,
            'meta' => $meta,
        ]));
    }

    public function logError(string $eventCode, string $message, array $context = []): void
    {
        $this->logger->error($this->format('error', $eventCode, [
            'message' => $message,
            'context' => $context,
        ]));
    }

    private function format(string $logType, string $eventCode, array $data): string
    {
        return sprintf(
            'WhatsApp [%s] [%s] %s',
            $eventCode,
            $logType,
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
