<?php

namespace godlike;


final class Log {
    public const HEADER_L = 3;
    public const HEADER_M = 2;
    public const HEADER_S = 1;

    /** @var Log */
    private static $instance;

    /**
     * @param string|null $path
     *
     * @return Log
     */
    public static function create(?string $path = null): Log {
        // This is an internal class for the godlike lib.
        // We must forbid using it in the app code.
        if (self::$instance) throw new \LogicException('Godlike Log cannot be constructed more than once.');
    
        if ($path !== null) {
            // Require writable file path.
            self::validatePath($path, true);
        }
        
        self::$instance = new self($path);
        return self::$instance;
    }
    
    /**
     * @param string $path
     * @param bool   $throw
     *
     * @return bool
     */
    private static function validatePath(string $path, bool $throw = true): bool {
        if (!@file_exists($path)) $r = @is_writable(\dirname($path));
        else $r = @is_writable($path);
        
        if ($throw && !$r) {
            throw new \Exception('File path is not writable: ' . $path);
        }
        
        return $r;
    }

    /**
     * @var string
     */
    private $path;

    /**
     * @param string|null $path
     */
    private function __construct(?string $path = null) {
        $this->path = $path;
    }

    /**
     * @param string $title
     * @param int    $size
     */
    public function header(string $title, int $size = Log::HEADER_S): void {
        $string = "\n";

        if ($size >= Log::HEADER_L) {
            $string .= str_repeat('=', 80) . "\n";
            $string .= '| ' . $this->formatTitle($title, 78) . "\n";
            $string .= str_repeat('=', 80) . "\n";
        }

        elseif ($size === Log::HEADER_M) {
            $string .= '| ' . $this->formatTitle($title, 38) . "\n";
            $string .= str_repeat('-', 40);
        }

        else {
            $width = \strlen($title);
            $string  = '| ' . $this->formatTitle($title, $width) . "\n";
            $string .= str_repeat('-', $width + 2);
        }

        $this->write($string);
    }

    /**
     * @param string $string
     */
    public function text(string $string): void {
        $this->write($string);
    }
    
    /**
     *
     */
    public function reset(): void {
        $this->write('', false);
    }

    //

    /**
     * @param string $title
     * @param int    $width
     *
     * @return string
     */
    private function formatTitle(string $title, int $width): string {
        $title = str_replace(["\n", "\r"], [' ', ''], $title);
        $title = str_pad(substr($title, 0, $width), $width);

        return $title;
    }
    
    /**
     * @param string $string
     * @param bool   $append
     */
    private function write(string $string, bool $append = true): void {
        $path = $this->path ?: ini_get('error_log');
        @file_put_contents($path, "$string\n", $append ? FILE_APPEND : 0);
    }
}
