<?php

namespace godlike;

class Logger {
    /**
     * @param Clock $clock
     * @param Log  $log
     */
    public static function setup(Clock $clock, Log $log): void {}
    
    /**
     * @param string $title
     * @param bool   $silent
     * @param bool   $compact
     */
    public static function header(string $title, bool $silent = false, bool $compact = false): void {}
    
    /**
     * @param mixed $data
     * @param bool $silent
     * @param bool $pretty
     */
    public static function log($data, bool $silent = false, bool $pretty = true): void {}
    
    /**
     * @return array
     */
    public static function getMessages(): array {}
    
    /**
     *
     */
    public static function clear(): void {}
}

class Timer {
    /**
     *
     */
    public static function boot(): void {}

    /**
     * @param Clock $clock
     * @param Log  $log
     */
    public static function setup(Clock $clock, Log $log): void {}

    /**
     * @param string|null $key
     * @param bool        $silent
     * @param bool        $real
     */
    public static function tick(?string $key = null, bool $silent = false, bool $real = true): void {}

    /**
     * @return array
     */
    public static function getMessages(): array {}

    /**
     *
     */
    public static function clear(): void {}
}

class Snitch {
    /**
     * @return array
     */
    public static function time(): array {}

    /**
     * @return array
     */
    public static function rng(): array {}

    /**
     * @param string|null $key
     *
     * @return string[]|string
     */
    public static function env(?string $key = null): array {}

    /**
     * @return string
     */
    public static function phpinfo(): string {}

    /**
     * @param string|null $key
     *
     * @return string[]|string
     */
    public static function ini(?string $key = null): array {}

    /**
     * @return string[]
     */
    public static function extensions(): array {}
}
