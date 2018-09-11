<?php

namespace godlike;


class SeedTime implements \JsonSerializable {
    /** @var int|null */
    private $timestamp;

    /** @var float|null */
    private $scale;

    /** @var int|null */
    private $stepMin;

    /** @var int|null */
    private $stepMax;
    
    /** @var int|null */
    private $lastHit;
    
    /** @var bool */
    private $changed;

    /**
     * @param array $data
     *
     * @return SeedTime
     */
    public static function load(array $data): SeedTime {
        return new SeedTime($data);
    }

    /**
     * @param int|null $timestamp
     * @param float|null $scale
     * @param int|null $stepMin
     * @param int|null $stepMax
     *
     * @return SeedTime
     */
    public static function create(?int $timestamp = null, ?float $scale = null, ?int $stepMin = null, ?int $stepMax = null): SeedTime {
        if (($stepMin === null && $stepMax !== null) || ($stepMin !== null && $stepMax === null)) {
            throw new \Exception('Min and max time step can either be both null or both non-null.');
        }
        if (($stepMin !== null || $stepMax !== null) && $scale !== null) {
            throw new \Exception('Time scale can be set without time step, but not together.');
        }
        if ($stepMin > $stepMax) throw new \Exception('Min step is bigger than max step.');

        return new SeedTime([
            'timestamp' => $timestamp,
            'scale'     => $scale,
            'stepMin'   => $stepMin,
            'stepMax'   => $stepMax,
            'lastHit'   => self::now()
        ]);
    }

    /**
     * @return int
     */
    private static function now(): int {
        return (int) floor(microtime(true) * 1000);
    }

    /**
     * @param array $data
     */
    private function __construct(array $data) {
        $this->timestamp = $data['timestamp'];
        $this->scale = $data['scale'];
        $this->stepMin = $data['stepMin'];
        $this->stepMax = $data['stepMax'];
        $this->lastHit = $data['lastHit'];
        $this->changed = false;

        // IMPORTANT: mysqld does not support lower than 1000 as timestamp.
        if ($this->timestamp < 10000) $this->timestamp = 10000;
    }

    /**
     * @return SeedTime
     */
    public function increment(): SeedTime {
        $timestamp = $this->timestamp;

        if ($this->scale !== null) {
            $timestamp = $this->timestamp ?? $this->lastHit;
            $timestamp += (int) round((self::now() - $this->lastHit) * $this->scale);
        }

        if ($this->stepMin !== null && $this->stepMax !== null) {
            $timestamp = $this->timestamp ?? self::now();
            /** @noinspection RandomApiMigrationInspection */
            $timestamp += $this->stepMin === $this->stepMax ? $this->stepMin : \mt_rand($this->stepMin, $this->stepMax);
        }

        $this->timestamp = $timestamp;
        $this->lastHit = self::now();
        $this->changed = true;
        
        return $this;
    }
    
    /**
     * @param bool $pdo
     * @param bool $libfaketime
     * @param string $pidFile
     *
     * @return SeedTime
     */
    public function apply(bool $pdo = false, bool $libfaketime = false, ?string $pidFile = null): SeedTime {
        if ($this->timestamp === null) {
            if ($libfaketime) {
                @unlink('/etc/faketimerc');
    
                if ($pidFile !== null) {
                    $pids = file_get_contents($pidFile);
                    foreach ($pids as $pid) posix_kill($pid, 30);
                }
            }
            
            return $this;
        }
        
        [$s, $us] = explode('.', \bcdiv((string) $this->timestamp, '1000', 6));
        
        $s = (int) ltrim($s, '0');
        $us = (int) ltrim($us, '0');
    
        if ($s > 4133980801000) throw new \InvalidArgumentException('Time seed can be max: ' . date('Y-m-d H:i:s', 4133980801000));
        if ($s < 0) throw new \InvalidArgumentException('Time seed can be min: ' . date('Y-m-d H:i:s', 0));
    
        if ($pdo) {
            /** @noinspection PhpUndefinedClassInspection */
            if (!method_exists(\PDO::class, 'setTimestamp')) {
                throw new \LogicException('PDO class is not overridden by Godlike.');
            }
            
            /** @noinspection PhpUndefinedClassInspection */
            \PDO::setTimestamp($this->timestamp * 1000);
        }
    
        if ($libfaketime) {
            $fp = fopen('/etc/faketimerc', 'r' . 'w+');
            for ($i = 0; $i < 5; $i ++) if (flock($fp, LOCK_EX)) break;
            if ($i > 4) throw new \Exception('Could not get the RNG seed lock!');
    
            ftruncate($fp, 0); rewind($fp);
            fwrite($fp, date('Y-m-d H:i:s', $s) . '|' . $us . "\n");
            fflush($fp); flock($fp, LOCK_UN); fclose($fp);
    
            if ($pidFile !== null) {
                $pids = file_get_contents($pidFile);
                foreach ($pids as $pid) posix_kill($pid, 30);
            }
        }
        
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isChanged(): bool {
        return $this->changed;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            'timestamp' => $this->timestamp,
            'scale' => $this->scale,
            'stepMin' => $this->stepMin,
            'stepMax' => $this->stepMax,
            'lastHit' => $this->lastHit
        ];
    }
}
