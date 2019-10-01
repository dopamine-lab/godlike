<?php

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection AutoloadingIssuesInspection */

//
// Standard time/date methods overridden to make them seedable.

function __godlike_timestamp_cache(bool $real = false) {
    if ($real) {
        $e = getenv('FAKETIME_REALTIME');
        if (is_string($e) && is_numeric($e) && strlen($e) > 0) $micro = $e;
        else $micro = bcmul(microtime_original(true), 1000000);
    } else {
        if (!isset($_SERVER['GODLIKE_TIMESTAMP'])) return null;
        $micro = $_SERVER['GODLIKE_TIMESTAMP'];
    }

    if (!$real && isset($_SERVER['GODLIKE_TIMESTAMP_CACHE']) && $_SERVER['GODLIKE_TIMESTAMP_CACHE'][0] === $micro) {
        return $_SERVER['GODLIKE_TIMESTAMP_CACHE'];
    }
    
    $milli = (int) \bcdiv($micro, '1000', 0);
    [$s, $u] = explode('.', \bcdiv($micro, '1000000', 6));
    [$z, $i] = explode('|', date('Z|I', (int) $s));
    
    $result = [$micro, $milli, (int) $s, (int) ltrim($u, '0'), -1 * (int) $z, (int) $i];
    if (!$real) $_SERVER['GODLIKE_TIMESTAMP_CACHE'] = $result;
    
    return $result;
}

//

if (function_exists('time') || !function_exists('time_original')) return;

//

function microtime($float = false) {
    if (!isset($_SERVER['GODLIKE_TIMESTAMP'])) return microtime_original($float);
    if ($float) return round($_SERVER['GODLIKE_TIMESTAMP'], 4);
    
    /** @noinspection PhpUnusedLocalVariableInspection */
    [$a, $b, $s, $u] = __godlike_timestamp_cache();
    
    return '0.' . $u . '00 ' . $s;
}

function gettimeofday($float = false) {
    if (!isset($_SERVER['GODLIKE_TIMESTAMP'])) return gettimeofday_original($float);
    if ($float) return round($_SERVER['GODLIKE_TIMESTAMP'] / 1000000, 5);
    
    /** @noinspection PhpUnusedLocalVariableInspection */
    [$a, $b, $s, $u, $z, $i] = __godlike_timestamp_cache();
    
    return ['sec' => $s, 'usec' => $u, 'minuteswest' => (int) ($z / 60), 'dsttime' => $i];
}

function time() {
    if (!isset($_SERVER['GODLIKE_TIMESTAMP'])) return time_original();
    return __godlike_timestamp_cache()[1];
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
    public static function createFromFormat($f, $t = 'now', $zone = null) {
        $date = parent::createFromFormat($f, $t, $zone);
        
        if ($t === null || $t === 'now' || $t === 'NOW') {
            /** @noinspection PhpUndefinedMethodInspection */
            $date->setTimestamp(time());
        }
        
        return $date;
    }
    
    public function __construct($t = 'now', $zone = null) {
        parent::__construct($t, $zone);
        
        if ($t === null || $t === 'now' || $t === 'NOW') {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->setTimestamp(time());
        }
    }
}

class DateTimeImmutable extends DateTimeImmutable_original {
    public static function createFromFormat($f, $t = 'now', $zone = null) {
        return parent::createFromMutable(DateTime::createFromFormat($f, $t, $zone));
    }
    
    public function __construct($t = 'now', $zone = null) {
        if ($t === 'now' || $t === 'NOW' || $t === null) {
            $date = new DateTime($t, $zone);
            parent::__construct($date->format('Y-m-d H:i:s'), $zone);
        } else {
            parent::__construct($t, $zone);
        }
    }
}
