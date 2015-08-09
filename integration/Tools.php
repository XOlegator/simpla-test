<?php

class Tools
{
    public static function getDate($log)
    {
        if (file_exists($log)) {
            return file_get_contents($log);
        } else {
            return date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s'))));
        }
    }

    public static function logger($message, $type, $errors = null)
    {
        $format = "[" . date('Y-m-d H:i:s') . "]";
        if (!is_null($errors) && is_array($errors)) {
            $message .= ":\n";
            foreach ($errors as $error) {
                $message .= "\t" . $error . "\n";
            }
        } else {
            $message .= "\n";
        }
        $logDir = '../../integration/log/'; 
        switch ($type) {
            case 'connect':
                $path = $logDir. "connect-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'customers':
                $path = $logDir . "customers-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'orders-info':
                $path = $logDir . "orders-info.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'orders-error':
                $path = $logDir . "orders-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'icml':
                $path = $logDir . "icml.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'history':
                $path = $logDir . "history-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'history-log':
                $path = $logDir . "history.log";
                file_put_contents($path, $message);
                break;
        }

    }
    
    public static function config($configFile)
    {
        if (file_exists($configFile)) {
            return include($configFile);
        } else {
            return null;
        }
    }
}
