<?php

namespace godlike;

require_once __DIR__ . '/../common/Log.php';
require_once __DIR__ . '/../common/Clock.php';


class Logger {
    /** @var Clock */
    private static $clock;

    /** @var Log */
    private static $log;

    /** @var string[] */
    private static $logs = [];

    /**
     * @param Clock $clock
     * @param Log  $log
     */
    public static function setup(Clock $clock, Log $log): void {
        if (self::$clock || self::$log) throw new \LogicException('Logger::setup can only be called once.');

        self::$clock = $clock;
        self::$log = $log;
    }

    /**
     * @param string $title
     * @param bool   $silent
     * @param bool   $compact
     */
    public static function header(string $title, bool $silent = false, bool $compact = false): void {
        if (!$silent && !self::$log) throw new \LogicException('Logger::header called before Logger::setup.');

        // Add the data to the logs array to be added to the HTTP response later.
        self::$logs[] = ['header', self::truncate($title)];

        if ($silent) return;

        self::$log->header($title, $compact ? 1 : 2);
    }

    /**
     * @param mixed $data
     * @param bool $silent
     * @param bool $pretty
     */
    public static function log($data, bool $silent = false, bool $pretty = true): void {
        if (!$silent && !self::$log) throw new \LogicException('Logger::log called before Logger::setup.');

        // Add the data to the logs array to be added to the HTTP response later.
        self::$logs[] = ['header', self::truncate(self::stringify($data))];

        if ($silent) return;

        // Log the data as plain text to the global log file.
        $text  = '[' . self::$clock->date(null, 3) . '] [LOGGER] ';
        $text .= self::stringify($data, $pretty);

        self::$log->text($text);
    }

    /**
     * @return array
     */
    public static function getMessages(): array {
        $messages = [];

        foreach (self::$logs as $i => $log) {
            $id = $i + 1;
            if ($log[0] === 'data') $messages[] = "Log-$id: $log[1]";
            if ($log[0] === 'log') $messages[] = "Log-$id: $log[1]";
        }

        return $messages;
    }

    /**
     *
     */
    public static function clear(): void {
        self::$logs = [];
    }

    //

    /**
     * @param mixed $data
     * @param bool $pretty
     *
     * @return string
     */
    private static function stringify($data, bool $pretty = false): string {
        if (!\is_string($data) && !\is_numeric($data)) {
            return json_encode($data, $pretty ? JSON_PRETTY_PRINT : 0);
        }

        return (string) $data;
    }

    /**
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    private static function truncate(string $string, int $length = 150): string {
        $string = str_replace(["\n", "\r"], [' ', ''], $string);
        if (\strlen($string) > 150) $string = substr($string, 0, $length) . ' ### TRUNCATED ...';

        return $string;
    }
}
