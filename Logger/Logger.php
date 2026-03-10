<?php

namespace Azguards\WhatsAppConnect\Logger;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
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

    public function addErrorLog($logData)
    {
        if (is_array($logData) || is_object($logData)) {
            $logData = json_encode($logData);
        }
        $this->error($logData);
    }

    public function addSuccessLog($logData)
    {
        if (is_array($logData) || is_object($logData)) {
            $logData = json_encode($logData);
        }
        $this->info($logData);
    }
}
