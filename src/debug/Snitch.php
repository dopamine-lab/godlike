<?php

namespace godlike;


class Snitch {
    /**
     * @return array
     */
    public static function time(): array {
        function collect() {
            $t = time();
            $ut = microtime(true);
            $f = 'Y-m-d H:i:s';

            return [
                'time' => $t,
                'microtime' => $ut,
                'gmdate' => [
                    gmdate($f),
                    gmdate($f, $t),
                    gmdate($f, $ut),
                ],
                'date' => [
                    date($f),
                    date($f, $t),
                    date($f, $ut),
                ],
                'timezone' => date_default_timezone_get(),
            ];
        }

        $times = [];
        $times[] = collect();

        usleep(300000);
        $times[] = collect();

        sleep(1);
        $times[] = collect();

        return $times;
    }

    /**
     * @return array
     */
    public static function rng(): array {
        /** @noinspection RandomApiMigrationInspection */
        return [
            'random_int' => random_int(1000, 9999),
            'mt_rand'    => mt_rand(1000, 9999),
            'rand'       => rand(1000, 9999),
        ];
    }

    /**
     * @param string|null $key
     *
     * @return string[]|string
     */
    public static function env(?string $key = null): array {
        if ($key !== null) return \getenv($key);
        return \getenv();
    }

    /**
     * @return string
     */
    public static function phpinfo(): string {
        // Intercept all phpinfo output.
        \ob_start();

        /** @noinspection ForgottenDebugOutputInspection */
        \phpinfo();

        // Clean the current OB nesting level.
        return \ob_get_clean();
    }

    /**
     * @param string|null $key
     *
     * @return string[]|string
     */
    public static function ini(?string $key = null): array {
        if ($key !== null) return \ini_get($key);
        return \ini_get_all(null, false);
    }

    /**
     * @return string[]
     */
    public static function extensions(): array {
        return \get_loaded_extensions();
    }
}
