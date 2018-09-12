<?php

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection AutoloadingIssuesInspection */

//
// Standard time/date methods overridden to make them seedable.

class FakeTime {
    /** @var int */
    private static $seconds;
    
    /** @var int */
    private static $milliseconds;
    
    /** @var int */
    private static $microseconds;
    
    /**
     * @param int  $microseconds
     * @param bool $requestTime
     */
    public static function set(int $microseconds, bool $requestTime = true): void {
        self::$microseconds = $microseconds;
        self::$milliseconds = (int) bcdiv($microseconds, '1000', 0);
        self::$seconds = (int) bcdiv($microseconds, '1000000', 0);
        
        if ($requestTime) $_SERVER['REQUEST_TIME'] = self::$seconds;
    }
    
    public static function get(int $type = 0): int {
        if ($type === 2) return self::$microseconds;
        if ($type === 1) return self::$milliseconds;
        return self::$seconds;
    }
}

if (function_exists('time')) return;


function time() {
    return FakeTime::get();
}

function microtime($float = false) {
    $s = FakeTime::get();
    $t = FakeTime::get(2);
    $us = (string) ($t - $s * 1000000);
    $us = str_pad($us, 6, '0', STR_PAD_LEFT);
    
    if ($float) return (float) ($s . '.' . substr($us, -4));
    return '0.' . $us . '00 ' . $s;
    
}

function gettimeofday($float = false) {
    $s = FakeTime::get();
    $t = FakeTime::get(2);
    $us = (string) ($t - $s * 1000000);
    $us = str_pad($us, 6, '0', STR_PAD_LEFT);
    
    if ($float) return (float) ($s . '.' . substr($us, -5));
    
    return [
        'sec' => $s,
        'usec' => $t - $s * 1000000,
        'minuteswest' => (int) (date('Z', $t) / 60),
        'dsttime' => (bool) (int) date('I', $t)
    ];
}

// TODO:
function mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null, $is_dst = -1) {}
function gmmktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null, $is_dst = -1) {}

function strtotime($s, $t = null) {return \strtotime_original($s, ($t === 'now' || $t === null) ? time() : $t);}
function localtime($t = null, $assoc = null) {return \localtime_original($t ?? time(), $assoc);}

function date($f, $t = null) {return \date_original($f, $t ?? time());}
function gmdate($f, $t = null) {return \gmdate_original($f, $t ?? time());}
function idate($f, $t = null) {return \idate_original($f, $t ?? time());}
function strftime($f, $t = null) {return \strftime_original($f, $t ?? time());}
function gmstrftime($f, $t = null) {return \gmstrftime_original($f, $t ?? time());}
function getdate($t = null) {return \getdate_original($t ?? time());}
function unixtojd($t = null) {return \unixtojd_original($t ?? time());}

function date_create($t = null, DateTimeZone $zone = null) {return \date_create_original();}
function date_create_from_format($f, $t, $zone = null) {return \date_create_from_format_original();}
function date_create_immutable($t = null, DateTimeZone $zone = null) {return \date_create_immutable_original();}
function date_create_immutable_from_format($f, $t, DateTimeZone $zone = null) {return \date_create_immutable_from_format_original();}


//class DateTime extends DateTime_original {
//    public static function createFromFormat($f, $t, DateTimeZone $zone = null) {
//        parent::createFromFormat($f, $t, $zone); // TODO: Change the autogenerated stub
//    }
//
//    public function __construct(string $t = 'now', DateTimeZone $zone = null) { parent::__construct($t, $zone); }
//
//    public static function __set_state($an_array) {
//        // TODO: Implement __set_state() method.
//    }
//
//}
//
//class DateTimeImmutable extends DateTimeImmutable_original {
//    public static function createFromFormat($f, $t, DateTimeZone $zone = null) {
//        parent::createFromFormat($f, $t, $zone); // TODO: Change the autogenerated stub
//    }
//
//    public function __construct(string $t = "now", ?DateTimeZone $zone = null) { parent::__construct($t, $zone); }
//}