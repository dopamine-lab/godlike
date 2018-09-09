<?php

namespace godlike;

require_once __DIR__ . '/../common/Log.php';
require_once __DIR__ . '/../common/Clock.php';


class Timer {
    /** @var Clock */
    private static $clock;

    /** @var Log */
    private static $log;

    /** @var array */
    private static $timers = [];

    /** @var int */
    private static $timeBoot;

    /**
     *
     */
    public static function boot(): void {
        if (self::$timeBoot !== null) throw new \LogicException('Timer::boot can only be called once.');

        self::$timeBoot = [self::$clock->micro(true), self::$clock->micro(false)];
    }

    /**
     * @param Clock $clock
     * @param Log  $log
     */
    public static function setup(Clock $clock, Log $log): void {
        if (self::$clock || self::$log) throw new \LogicException('Timer::setup can only be called once.');

        self::$clock = $clock;
        self::$log = $log;
    }

    /**
     * @param string|null $key
     * @param bool        $silent
     * @param bool        $real
     */
    public static function tick(?string $key = null, bool $silent = false, bool $real = true): void {
        if (self::$timeBoot === null) throw new \LogicException('Timer::tick called before Timer::boot.');
        if (!$silent && !self::$log) throw new \LogicException('Timer::tick called before Timer::setup.');

        // Key should always have a value
        $key = $key ?: 'MAIN';

        // Timer is not created yet. Let's do it.
        if (!isset(self::$timers[$key])) self::$timers[$key] = [
            'real' => $real, 'silent' => $silent,
            'time' => self::time($real),
            'ticks' => []
        ];

        $timer = &self::$timers[$key];

        $id = \count($timer['ticks']) + 1;
        $time = self::time($timer['real']);
        $offset = $time - $timer['time'];
        $duration = $time - ($id > 1 ? $timer['ticks'][$id - 2]['time'] : $timer['time']);

        $timer['ticks'][] = [$duration, $offset, $time];

        if ($timer['silent']) return;

        // Log the times as plain text to the global log file.
        $text  = '[' . self::$clock->date(null, 3) . '] [TIMER] ';
        $text .= "[$key] #$id: $duration, $offset, $time";

        self::$log->text($text);
    }

    /**
     * @return array
     */
    public static function getMessages(): array {
        $messages = [];

        foreach (self::$timers as $key => $timer) {
            foreach ($timer['ticks'] as $i => $tick) {
                $id = $i + 1;
                $messages[] = "Timer-$key-$id: $tick[0], $tick[1], $tick[2]";
            }
        }

        return $messages;
    }

    /**
     *
     */
    public static function clear(): void {
        self::$timers = [];
    }

    //

    /**
     * @param bool $real
     *
     * @return int
     */
    private static function time(bool $real = true): int {
        return self::$clock->micro($real) - self::$timeBoot[$real ? 0 : 1];
    }
}


// Call boot method here to log the php request/process boot timestamp.
// Will be used to calculate offsets.
Timer::boot();
