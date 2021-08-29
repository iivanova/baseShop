<?php
namespace App\Helpers;

class MyLogger {

    const INFO = '[INFO]';
    const WARNING = '[WARNING]';
    const ERROR = '[ERROR]';

    public static function log($message, $messageType = MyLogger::WARNING) {

        try {
            if (!is_string($message)) {
                return false;
            }

            if ($messageType != MyLogger::WARNING && $messageType != MyLogger::INFO && $messageType != MyLogger::ERROR) {
                $messageType = MyLogger::WARNING;
            }

            $funcName = '';
            $request_uri = '';
            try {
                $backtrace = debug_backtrace();
                if (is_array($backtrace) && isset($backtrace[1]['function'])) {
                    $funcName = $backtrace[1]['function'];
                }
                if (isset($_SERVER['REQUEST_URI'])){
                    $request_uri = $_SERVER['REQUEST_URI'];
                }
            } catch (Exception $ex) {
                
            }

            $date = new \DateTime();
            $dateStr = $date->format('Y-m-d H:i:s');
            $messageToLog = "\n" . $dateStr . " " . $messageType . " (function " . $funcName . " : URL: " . $request_uri .  " ): " . $message . "\n";

            $logFile = '';
            if (!empty($GLOBALS["config"]["log_file"]) && file_exists($GLOBALS["config"]["log_file"])) {
                $logFile = $GLOBALS["config"]["log_file"];
            } else {
                $logFile = dirname(__FILE__) . "/../logs/output.log";
            }

            if (!file_exists($logFile)) {
                file_put_contents($logFile, $messageToLog);
            } else {
                file_put_contents($logFile, $messageToLog, FILE_APPEND);
            }
        } catch (Exception $e) {
            
        }
    }

}
