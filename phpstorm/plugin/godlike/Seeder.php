<?php

namespace godlike;

class Seeder {
    /**
     * @param Storage $storage
     *
     * @return Seeder
     */
    public static function create(Storage $storage): Seeder {}

    /**
     * @param int|null $rng
     * @param int|null $time
     * @param float|null $timeScale
     * @param int|null $timeStepMin
     * @param int|null $timeStepMax
     */
    public function reset(?int $rng, ?int $time, ?float $timeScale, ?int $timeStepMin, ?int $timeStepMax): void {}

    /**
     * @param int|null $tmpRng
     * @param int|null $tmpTime
     */
    public function exec(?int $tmpRng = null, ?int $tmpTime = null): void {}

    /**
     * @return SeedRng
     */
    public function getRng(): SeedRng {}

    /**
     * @return SeedTime
     */
    public function getTime(): SeedTime {}
}

class SeedRng implements \JsonSerializable {
    /**
     * @param array $data
     *
     * @return SeedRng
     */
    public static function load(array $data): SeedRng {}
    
    /**
     * @param int|null $seed
     *
     * @return SeedRng
     */
    public static function create(?int $seed = null): SeedRng {}
    
    /**
     * @return SeedRng
     */
    public function increment(): SeedRng {}
    
    /**
     * @return SeedRng
     */
    public function apply(): SeedRng {}
    
    /**
     * @return bool
     */
    public function isChanged(): bool {}
    
    /**
     * @return array
     */
    public function jsonSerialize(): array {}
}

class SeedTime implements \JsonSerializable {
    /**
     * @param array $data
     *
     * @return SeedTime
     */
    public static function load(array $data): SeedTime {}
    
    /**
     * @param int|null $timestamp
     * @param float|null $scale
     * @param int|null $stepMin
     * @param int|null $stepMax
     *
     * @return SeedTime
     */
    public static function create(?int $timestamp = null, ?float $scale = null, ?int $stepMin = null, ?int $stepMax = null): SeedTime {}
    
    /**
     * @return SeedTime
     */
    public function increment(): SeedTime {}
    
    /**
     * @param bool $pdo
     * @param bool $libfaketime
     * @param string $pidFile
     *
     * @return SeedTime
     */
    public function apply(bool $pdo = false, bool $libfaketime = false, ?string $pidFile = null): SeedTime {}
    
    /**
     * @return bool
     */
    public function isChanged(): bool {}
    
    /**
     * @return array
     */
    public function jsonSerialize(): array {}
}
