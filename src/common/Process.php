<?php

namespace godlike;


final class Process {
    /** @var Process */
    private static $instance;
    
    /**
     * @param Storage     $storage
     * @param string|null $name
     * @param string[]    $tags
     *
     * @return Process
     */
    public static function create(Storage $storage, string $name = null, array $tags = []): Process {
        // This is an internal class for the godlike lib.
        // We must forbid using it in the app code.
        if (self::$instance) throw new \LogicException('Godlike Process cannot be constructed more than once.');
        
        /** @var array $db */
        $db = $storage->getMany('pid', ['id', 'tags']);
        
        if (!isset($db['tags'])) $db['tags'] = [];
        if (!isset($db['id'])) $db['id'] = 1;
        else $db['id'] ++;
    
        $id = $db['id'];
        $tagIds = [];
        
        foreach (array_unique($tags) as $tag) {
            if (!isset($db['tags'][$tag])) $db['tags'][$tag] = 1;
            else $db['tags'][$tag] ++;

            $ids[$tag] = $db['tags'][$tag];
        }

        $storage->setMany('pid', $db);

        self::$instance = new self($id, $tagIds, $name);
        return self::$instance;
    }
    
    /**
     * @var int
     */
    private $id;

    /**
     * @var int[]
     */
    private $tagIds;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $time;
    
    /**
     * @param int         $id
     * @param int[]       $tagIds
     * @param string|null $name
     */
    private function __construct(int $id, array $tagIds = [], string $name = null) {
        $this->id = $id;
        $this->tagIds = $tagIds;
        $this->name = $name;
        $this->time = (int) bcmul((string) microtime(true), '1000', 0);
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getTagIds(): array {
        return $this->tagIds;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getTime(): int {
        return $this->time;
    }
    
    /**
     * @param bool $name
     *
     * @return string
     */
    public function getFullId(bool $name = false): string {
        $full = [];
        
        foreach ($this->tagIds as $tag => $id) {
            $full[] = "$tag => $id";
        }
        
        $id = $this->id;
        if ($name && $this->name) $id .= " ($this->name)";
        
        if (empty($full)) return $id;
        return $id . ' (' . implode(', ', $full) . ' )';
    }
    
    /**
     * @param string|null $format
     *
     * @return string
     */
    public function getDate($format = null): string {
        return gmdate($format ?: 'Y-m-d H:i:s', $this->time / 1000);
    }
}
