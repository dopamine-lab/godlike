<?php

/** @noinspection PhpUndefinedClassInspection */
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

function mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null) {
    return mktime($hour ?? gmdate('H'), $minute ?? gmdate('i'), $second ?? gmdate('s'), $month ?? gmdate('n'), $day ?? gmdate('j'), $year ?? gmdate('Y'));
}
function gmmktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null) {
    return gmmktime($hour ?? gmdate('H'), $minute ?? gmdate('i'), $second ?? gmdate('s'), $month ?? gmdate('n'), $day ?? gmdate('j'), $year ?? gmdate('Y'));
}

function strtotime($s, $t = null) {return \strtotime_original($s, ($t === 'now' || $t === 'NOW' || $t === null) ? time() : $t);}
function localtime($t = null, $assoc = null) {return \localtime_original($t ?? time(), $assoc);}

function date($f, $t = null) {return \date_original($f, $t ?? time());}
function gmdate($f, $t = null) {return \gmdate_original($f, $t ?? time());}
function idate($f, $t = null) {return \idate_original($f, $t ?? time());}
function strftime($f, $t = null) {return \strftime_original($f, $t ?? time());}
function gmstrftime($f, $t = null) {return \gmstrftime_original($f, $t ?? time());}
function getdate($t = null) {return \getdate_original($t ?? time());}
function unixtojd($t = null) {return \unixtojd_original($t ?? time());}

function date_create($t = 'now', DateTimeZone $zone = null) {return new DateTime($t, $zone);}
function date_create_from_format($f, $t, $zone = null) {return DateTime::createFromFormat($f, $t, $zone);}
function date_create_immutable($t = 'now', DateTimeZone $zone = null) {return new DateTimeImmutable($t, $zone);}
function date_create_immutable_from_format($f, $t, DateTimeZone $zone = null) {return DateTimeImmutable::createFromFormat($f, $t, $zone);}


class DateTime extends DateTime_original {
    public static function createFromFormat($f, $t = 'now', ?DateTimeZone $zone = null) {
        $date = parent::createFromFormat($f, $t, $zone);
        
        if ($t === null || $t === 'now' || $t === 'NOW') {
            /** @noinspection PhpUndefinedMethodInspection */
            $date->setTimestamp(time());
        }
        
        return $date;
    }
    
    public function __construct($t = 'now', ?DateTimeZone $zone = null) {
        parent::__construct($t, $zone);
        
        if ($t === null || $t === 'now' || $t === 'NOW') {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->setTimestamp(time());
        }
    }
}

class DateTimeImmutable extends DateTimeImmutable_original {
    public static function createFromFormat($f, $t = 'now', ?DateTimeZone $zone = null) {
        return parent::createFromMutable(DateTime::createFromFormat($f, $t, $zone));
    }
    
    public function __construct($t = 'now', ?DateTimeZone $zone = null) {
        if ($t === 'now' || $t === 'NOW' || $t === null) {
            $date = new DateTime($t, $zone);
            parent::__construct($date->format('Y-m-d H:i:s'), $zone);
        } else {
            parent::__construct($t, $zone);
        }
    }
}
