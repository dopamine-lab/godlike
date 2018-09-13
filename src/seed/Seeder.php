<?php

namespace godlike;

require_once __DIR__ . '/SeedRng.php';
require_once __DIR__ . '/SeedTime.php';


class Seeder {
    /** @var Seeder */
    private static $instance;
    
    /**
     * @param Storage $storage
     * @param Clock   $clock
     *
     * @return Seeder
     */
    public static function create(Storage $storage, Clock $clock): Seeder {
        // This is an internal class for the godlike lib.
        // We must forbid using it in the app code.
        if (self::$instance) throw new \LogicException('Godlike Seeder cannot be constructed more than once.');

        self::$instance = new self($storage, $clock);
        return self::$instance;
    }
    
    /** @var Storage */
    private $storage;
    
    /** @var Clock */
    private $clock;

    /** @var SeedRng */
    private $rng;

    /** @var SeedTime */
    private $time;
    
    /**
     * @param Storage $storage
     * @param Clock   $clock
     */
    private function __construct(Storage $storage, Clock $clock) {
        $this->storage = $storage;
        $this->clock = $clock;
    }

    /**
     * @param int|null $rng
     * @param int|null $time
     * @param float|null $timeScale
     * @param int|null $timeStepMin
     * @param int|null $timeStepMax
     */
    public function reset(?int $rng, ?int $time, ?float $timeScale, ?int $timeStepMin, ?int $timeStepMax): void {
        // Create new seeds
        $this->rng  = SeedRng::create($rng);
        $this->time = SeedTime::create($time, $timeScale, $timeStepMin, $timeStepMax);

        // Save to storage
        $this->storage->setMany('seed', ['rng'  => $this->rng, 'time' => $this->time]);
    }

    /**
     * @param int|null $tmpRng
     * @param int|null $tmpTime
     */
    public function exec(?int $tmpRng = null, ?int $tmpTime = null): void {
        // Get current real time
        $now = $this->clock->milli(true);
        
        // Load from storage
        ['rng' => $r, 'time' => $t] = $this->storage->getMany('seed', ['rng', 'time']);
    
        $this->rng  = $r ? SeedRng::load($r) : SeedRng::create();
        $this->time = $t ? SeedTime::load($t) : SeedTime::create();
    
        
        if ($tmpRng === null) $this->rng->increment()->apply();
        else SeedRng::create($tmpRng)->apply();
    
        if ($tmpTime === null) $this->time->increment($now)->apply();
        else SeedTime::create($tmpTime)->apply();
        
        $cr = $this->rng->isChanged();
        $ct = $this->time->isChanged();
        $cb = $cr && $ct;
    
        // Save to storage optimized
        if ($cb)     $this->storage->setMany('seed', ['rng' => $this->rng, 'time' => $this->time]);
        elseif ($cr) $this->storage->set('seed', 'rng',  $this->rng);
        elseif ($ct) $this->storage->set('seed', 'time', $this->time);
    }

    /**
     * @return SeedRng
     */
    public function getRng(): SeedRng {
        return $this->rng;
    }

    /**
     * @return SeedTime
     */
    public function getTime(): SeedTime {
        return $this->time;
    }
}
