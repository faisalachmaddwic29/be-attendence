<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class LogHelper
{
    public static function info($message, $data = [])
    {
        self::log('info', $message, $data);
    }

    public static function warning($message, $data = [])
    {
        self::log('warning', $message, $data);
    }

    public static function error($message, $data = [])
    {
        self::log('error', $message, $data);
    }

    private static function log($level, $message, $data = [])
    {
        $data = array_merge([
            'timestamp' => now()->toDateTimeString(),
        ], $data);

        Log::log($level, $message, $data);
    }
}
