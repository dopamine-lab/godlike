<?php

namespace godlike;


class SeedRng implements \JsonSerializable {
    /** @var int|null */
    private $seed;

    /** @var int|null */
    private $count;
    
    /** @var bool */
    private $changed;

    /**
     * @param array $data
     *
     * @return SeedRng
     */
    public static function load(array $data): SeedRng {
        return new SeedRng($data);
    }

    /**
     * @param int|null $seed
     *
     * @return SeedRng
     */
    public static function create(?int $seed = null): SeedRng {
        return new SeedRng(['seed' => $seed, 'count' => 0], true);
    }
    
    /**
     * @param array $data
     * @param bool  $new
     */
    private function __construct(array $data, bool $new = false) {
        $this->seed = $data['seed'] ?? null;
        $this->count = $data['count'] ?? 0;
        $this->changed = $new;
    }

    /**
     * @return SeedRng
     */
    public function increment(): SeedRng {
        if ($this->seed === null) return $this;
        
        $seed = $this->seed;
        $count = $this->count + 1;

        if ($count >= 100) {
            // Increment the seed every X numbers to make it (much) faster.
            $seed ++;
            $count = 0;
        }

        $this->seed = $seed;
        $this->count = $count;
        $this->changed = true;

        return $this;
    }

    /**
     * @return SeedRng
     */
    public function apply(): SeedRng {
        if ($this->seed === null) return $this;
        
        mt_srand($this->seed);
        for ($i = 0, $l = $this->count; $i < $l; $i ++) mt_rand();
        mt_srand(mt_rand());

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
            'seed' => $this->seed,
            'count' => $this->count
        ];
    }
}
