<?php

class Logger
{
    public static function info($message)
    {
        trigger_error($message, E_USER_NOTICE);
        return $message;
    }
    public static function warn($message)
    {
        trigger_error($message, E_USER_WARNING);
        return $message;
    }
    public static function error($message)
    {
        trigger_error($message, E_USER_ERROR);
        return $message;
    }
    public static function deprecated($message)
    {
        trigger_error($message, E_USER_DEPRECATED);
        return $message;
    }
}