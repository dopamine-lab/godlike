<?php

namespace godlike;


final class Clock {
    /** @var Clock */
    private static $instance;

    /**
     *
     */
    public static function create(): Clock {
        // This is an internal class for the godlike lib.
        // We must forbid using it in the app code.
        if (self::$instance) throw new \LogicException('Godlike Clock cannot be constructed more than once.');

        self::$instance = new self();
        return self::$instance;
    }

    /**
     *
     */
    private function __construct() {}

    /**
     * @param bool $real
     *
     * @return int
     */
    public function unix(bool $real = false): int {
        return $this->now($real, 1000000);
    }

    /**
     * @param bool $real
     *
     * @return int
     */
    public function milli(bool $real = false): int {
        return $this->now($real, 1000);
    }

    /**
     * @param bool $real
     *
     * @return int
     */
    public function micro(bool $real = false): int {
        return $this->now($real, 1);
    }

    /**
     * @param string|null $format
     * @param int $fraction
     * @param bool $real
     *
     * @return string
     */
    public function date(string $format = null, int $fraction = 0, bool $real = false): string {
        if (!$format) $format = 'Y-m-d H:i:s';

        // Regular date format
        if ($fraction <= 0) return gmdate($format, $this->unix($real));

        return $this->format($this->micro($real), $format, $fraction);
    }

    /**
     * @param int $microtime
     * @param string|null $format
     * @param int $fraction
     *
     * @return string
     */
    public function format(int $microtime, string $format = null, int $fraction = 0): string {
        if (!$format) $format = 'Y-m-d H:i:s';

        $fraction = max(min($fraction, 6), 0);
        $operand = '1' . str_repeat('0', $fraction);

        // Fraction date format
        $t = (int) bcdiv($microtime, $operand, 0);
        $f = (int) bcmod($microtime, $operand);

        return gmdate($format, $t) . '.' . str_pad($f, 3, '0', STR_PAD_LEFT);
    }

    //

    /**
     * @param bool $real
     * @param int  $divisor
     *
     * @return int
     */
    private function now(bool $real, int $divisor): int {
        $t = microtime(true) * 1000000;
        $e = getenv('FAKETIME_REALTIME');

        // If env var is empty or wrong format, maybe there is only realtime anyway.
        if (!$real || !is_numeric($e)) $e = (string) $t;

        return (int) bcdiv($e, (string) $divisor, 0);
    }
}
