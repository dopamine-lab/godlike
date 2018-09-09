<?php

namespace godlike;


final class Storage {
    /** @var Storage */
    private static $instance;
    
    /**
     * @param string $path;
     *
     * @return Storage
     */
    public static function create(string $path): Storage {
        // This is an internal class for the godlike lib.
        // We must forbid using it in the app code.
        if (self::$instance) throw new \LogicException('Godlike Storage cannot be constructed more than once.');
        
        self::$instance = new self($path);
        return self::$instance;
    }
    
    /** @var string */
    private $path;
    
    /** @var array */
    private $fp;
    
    /** @var array */
    private $data;
    
    /**
     * @param string $path
     */
    private function __construct(string $path) {
        if (!\is_dir($path)) throw new \InvalidArgumentException('Storage path is not a valid directory: ' . $path);
        if (!\is_writable($path)) throw new \InvalidArgumentException('Storage directory is not writable: ' . $path);
        
        $this->path = $path;
        $this->fp = [];
        $this->data = [];
    }
    
    /**
     * @param string $file
     * @param string $key
     * @param bool   $reload
     *
     * @return mixed
     */
    public function get(string $file, string $key, bool $reload = false) {
        if ($reload || !isset($this->data[$file])) {
            // Load data from file again.
            $this->open($file, true);
        }
        
        if (!isset($this->data[$file][$key])) return null;
        return json_decode($this->data[$file][$key], true);
    }

    /**
     * @param string $file
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $file, string $key, $value): void {
        $this->open($file);
        $this->data[$file][$key] = json_encode($value);
        $this->close($file, true);
    }

    /**
     * @param string $file
     * @param array  $keys
     * @param bool   $reload
     *
     * @return array
     */
    public function getMany(string $file, array $keys, bool $reload = false): array {
        if ($reload || !isset($this->data[$file])) {
            // Load data from file again.
            $this->open($file, true);
        }

        $result = [];
        foreach ($keys as $key) {
            $value = $this->data[$file][$key] ?? null;
            if ($value !== null) $value = json_decode($value, true);

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param string $file
     * @param array  $paris
     */
    public function setMany(string $file, array $paris): void {
        $this->open($file);

        foreach ($paris as $key => $value) {
            $this->data[$file][$key] = json_encode($value);
        }

        $this->close($file, true);
    }
    
    /**
     * @param string $file
     */
    public function reset(string $file): void {
        // Validate file
        $this->validate($file, false, true);
    
        // Reset file
        file_put_contents($this->path . '/' . $file . '.json', '{}');
    }
    
    //
    
    /**
     * @param string $file
     * @param bool   $close
     */
    private function open(string $file, bool $close = false): void {
        // Validate file
        $this->validate($file, false, true);
        
        // Open file handle
        $path = $this->path . '/' . $file . '.json';
        $size = filesize($path);
        $fp = fopen($path, 'r' . 'w+');
        
        // Try to get exclusive blocking lock 5 times
        for ($i = 0; $i < 5; $i ++) if (flock($fp, LOCK_EX)) break;
        if ($i > 4) throw new \Exception('Could not get storage file lock: ' . $file);
    
        // Decode JSON content
        $this->data[$file] = json_decode(fread($fp, $size), true);
        $this->fp[$file] = $fp;
        
        // Immediately close file on demand
        if ($close) $this->close($file);
    }
    
    /**
     * @param string $file
     * @param bool   $save
     */
    private function close(string $file, bool $save = false): void {
        // Validate file
        $this->validate($file, true);
        
        // Save file
        if ($save) {
            // Encode JSON content
            $content = json_encode($this->data[$file], JSON_PRETTY_PRINT);
    
            // Truncate and save content
            ftruncate($this->fp[$file], 0);
            rewind($this->fp[$file]);
            fwrite($this->fp[$file], $content);
            fflush($this->fp[$file]);
        }
        
        // Close file
        flock($this->fp[$file], LOCK_UN);
        fclose($this->fp[$file]);
    
        $this->fp[$file] = null;
        unset($this->fp[$file]);
    }
    
    /**
     * @param string $file
     * @param bool   $opened
     * @param bool   $touch
     */
    private function validate(string $file, bool $opened = null, bool $touch = false): void {
        if (!preg_match('/^[a-zA-Z0-9]+$/', $file)) {
            throw new \InvalidArgumentException('Storage file name must be only alpha-numeric.');
        }
    
        $fp = $this->fp[$file] ?? null;
    
        if ($fp &&  $opened)    return;
        if ($fp && !$opened)    throw new \Exception('File is already loaded: ' . $file);
        if ($opened && !$touch) throw new \Exception('File is not loaded yet: ' . $file);
    
        $path = $this->path . '/' . $file . '.json';
        $exists = @is_file($path);
        $writable = @is_writable($exists ? $path : \dirname($path));
    
        if (!$writable)          throw new \Exception('File is not writable: ' . $path);
        if (!$exists && !$touch) throw new \Exception('File does not exist: ' . $file);
        
        if (!$exists)         file_put_contents($path, '{}');
        if (!@is_file($path)) throw new \Exception('File cannot be created: ' . $file);
    }
}
