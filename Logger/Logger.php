<?php

namespace Azguards\WhatsAppConnect\Logger;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    /**
     * Logged As Info Data
     *
     * @param string|array|int $url
     * @param string|array|int $requestType
     * @param string|array|int $message
     * @param string|array|int $headers
     * @param string|array|int $params
     * @param string|array|int $response
     * @return void
     */
    public function loggedAsInfoData($url, $requestType, $message, $headers, $params, $response)
    {
        $this->info("==============Start==============");
        $this->info("URL: " . $url);
        $this->info("RequestType: " . $requestType);
        $this->info("Message: " . $message);
        $this->info("Headers: " . json_encode($headers));
        $this->info("Params: " . json_encode($params));
        $this->info("Response: " . json_encode($response));
        $this->info("==============End===============");
    }

    /**
     * Add Error Log
     *
     * @param string|array|int $logData
     * @return void
     */
    public function addErrorLog($logData)
    {
        $this->error($logData);
    }

    /**
     * Add Success Log
     *
     * @param string|array|int|object $logData
     * @return void
     */
    public function addSuccessLog($logData)
    {
        $this->info($logData);
    }
}
