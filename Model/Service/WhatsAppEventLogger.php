<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Logger\Logger;

class WhatsAppEventLogger
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log that an event was triggered.
     *
     * @param string $eventCode
     * @param array $context
     * @return void
     */
    public function logEventTriggered(string $eventCode, array $context = []): void
    {
        $this->logger->info($this->format('event_triggered', $eventCode, $context));
    }

    /**
     * Log an outgoing payload.
     *
     * @param string $eventCode
     * @param array $payload
     * @param array $meta
     * @return void
     */
    public function logPayload(string $eventCode, array $payload, array $meta = []): void
    {
        $this->logger->info($this->format('payload', $eventCode, [
            'payload' => $payload,
            'meta' => $meta,
        ]));
    }

    /**
     * Log an API response payload.
     *
     * @param string $eventCode
     * @param array $response
     * @param array $meta
     * @return void
     */
    public function logApiResponse(string $eventCode, array $response, array $meta = []): void
    {
        $this->logger->info($this->format('api_response', $eventCode, [
            'response' => $response,
            'meta' => $meta,
        ]));
    }

    /**
     * Log an event error.
     *
     * @param string $eventCode
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logError(string $eventCode, string $message, array $context = []): void
    {
        $this->logger->error($this->format('error', $eventCode, [
            'message' => $message,
            'context' => $context,
        ]));
    }

    /**
     * Format a structured WhatsApp log message.
     *
     * @param string $logType
     * @param string $eventCode
     * @param array $data
     * @return string
     */
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
