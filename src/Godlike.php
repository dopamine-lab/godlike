<?php

namespace godlike;

require_once __DIR__ . '/std/Random.php';
require_once __DIR__ . '/std/Time.php';
require_once __DIR__ . '/pdo/PDO.php';
require_once __DIR__ . '/common/Clock.php';
require_once __DIR__ . '/common/Log.php';
require_once __DIR__ . '/common/Process.php';
require_once __DIR__ . '/common/Storage.php';
require_once __DIR__ . '/debug/Logger.php';
require_once __DIR__ . '/debug/Timer.php';
require_once __DIR__ . '/debug/Snitch.php';
require_once __DIR__ . '/seed/Seeder.php';


final class Godlike {
    /** @var Godlike */
    private static $instance;
    
    /**
     *
     */
    public static function enchant(): void {
        // Allow disable prepend or seed for the request.
        if (isset($_SERVER['HTTP_GODLIKE_NO_PREPEND']) && $_SERVER['HTTP_GODLIKE_NO_PREPEND']) return;
        
        self::$instance = new Godlike();
        $tags = self::string($_SERVER['HTTP_GODLIKE_REQUEST_TAGS'] ?? '');
        self::$instance->prepend([
            'name' => self::string($_SERVER['HTTP_GODLIKE_REQUEST_NAME'] ?? null),
            'tags' => preg_split('/\s*,\s*/', $tags, -1, PREG_SPLIT_NO_EMPTY),
            'rng' => self::int($_SERVER['HTTP_GODLIKE_SEED_RNG'] ?? null),
            'time' => self::int($_SERVER['HTTP_GODLIKE_SEED_TIME'] ?? null) ?: null,
            'logEnabled' => !self::bool($_SERVER['HTTP_GODLIKE_NO_LOG'] ?? null),
            'seedEnabled' => !self::bool($_SERVER['HTTP_GODLIKE_NO_SEED'] ?? null),
        ]);
    }
    
    /**
     *
     */
    public static function api(): void {
        if (!self::$instance) {
            self::$instance = new Godlike();
        }
    
        $cmd = self::string($_REQUEST['cmd'] ?? '');
        $params = [];
    
        if ($cmd === 'seed') {
            $params['rng'] = self::int($_REQUEST['rng'] ?? null);
            $params['time'] = self::int($_REQUEST['time'] ?? null) ?: null;
            $params['timeScale'] = self::float($_REQUEST['timeScale'] ?? null);
            $params['timeStepMin'] = self::int($_REQUEST['timeStepMin'] ?? null);
            $params['timeStepMax'] = self::int($_REQUEST['timeStepMax'] ?? null);
        } elseif ($cmd === 'reset') {
            $params['seed'] = self::bool($_REQUEST['seed'] ?? null);
            $params['opcache'] = self::bool($_REQUEST['opcache'] ?? null);
            $params['pid'] = self::bool($_REQUEST['pid'] ?? null);
            $params['log'] = self::bool($_REQUEST['log'] ?? null);
        } elseif ($cmd === 'config') {
            $params = $_REQUEST ?? [];

            unset($params['cmd']);
        }
    
        try {
            $result = self::$instance->command($cmd, $params);
        } catch (\Throwable $error) {
            $result = null;
        }
    
        self::respond($result, $error ?? null);
    }

    //

    /** @var Clock */
    private $clock;

    /** @var Log */
    private $log;

    /** @var Storage */
    private $storage;

    /** @var Process */
    private $process;

    /** @var Seeder */
    private $seeder;

    /** @var bool */
    private $seedEnabled;
    
    /** @var int */
    private $realtime;
    
    /** @var array */
    private $info;
    
    /** @var bool */
    private $strictEnabled;
    
    /** @var bool */
    private $strictForceDeclare;
    
    /** @var bool */
    private $headersEnabled;
    
    /** @var bool */
    private $logEnabled;
    
    /** @var string */
    private $logPath;
    
    /** @var bool */
    private $statsEnabled;
    
    /** @var bool */
    private $statsDuration;
    
    /** @var bool */
    private $statsQueries;
    
    /** @var bool */
    private $statsTransactions;

    /** @var string  */
    private $configFile;

    /** @var array */
    private $_config;
    
    /**
     *
     */
    private function __construct() {
        $this->configFile = dirname(__DIR__) . '/config.ini';
        $this->_config = [];
    }

    /**
     * @param string|null $name
     * @param array       $tags
     */
    private function init(?string $name = null, array $tags = []): void {
        if ($this->process) return;
    
        if (file_exists($this->configFile)) {
            $this->_config = parse_ini_file($this->configFile) ?? [];
        }

        $this->seedEnabled = true;
        $this->strictEnabled = self::bool($this->_config['strict_enabled'] ?? true);
        $this->strictForceDeclare = self::bool($this->_config['strict_force_declare'] ?? true);
        $this->headersEnabled = self::bool($this->_config['headers_enabled'] ?? true);
        $this->logEnabled = self::bool($this->_config['log_enabled'] ?? true);
        $this->logPath = self::string($this->_config['log_path'] ?? null);
        $this->statsEnabled = self::bool($this->_config['stats_enabled'] ?? true);
        $this->statsDuration = self::bool($this->_config['stats_duration'] ?? true);
        $this->statsQueries = self::bool($this->_config['stats_queries'] ?? true);
        $this->statsTransactions = self::bool($this->_config['stats_transactions'] ?? true);
        
        //

        $this->clock   = Clock::create();
        $this->log     = Log::create($this->logPath);
        $this->storage = Storage::create(\dirname(__DIR__) . '/storage');
        $this->process = Process::create($this->storage, $name, $tags);
        $this->seeder  = Seeder::create($this->storage, $this->clock);
    }

    /**
     * @param array $params
     */
    private function prepend(array $params = []): void {
        if ($this->process) throw new \LogicException('Godlike::prepend can only be called once per request/process.');

        // Call init to setup the whole request/process.
        $this->init($params['name'] ?? null, $params['tags'] ?? []);

        // Enable or disable based on params from enchant
        $this->logEnabled = $params['logEnabled'] ?? $this->logEnabled;
        $this->seedEnabled = $params['seedEnabled'] ?? $this->seedEnabled;

        // Append file is not called if there is a fatal error, se we do it this way.
        register_shutdown_function(function () {
            $this->append();
        });

        // Execute the seeder (with temp seed if provided).
        if (self::$instance->seedEnabled) {
            $this->seeder->exec($params['rng'] ?? null, $params['time'] ?? null);
        }

        // Collect startup info.
        $this->realtime = $this->clock->micro(true);
        $this->info = [
            'request' => [
                'id'       => $this->process->getFullId(true),
                'time'     => $this->process->getTime(),
                'date'     => $this->process->getDate(),
                'name'     => $this->process->getName(),
                'duration' => 0,
            ],
            'seed'    => [
                'rng'      => self::$instance->seedEnabled ? json_encode($this->seeder->getRng()) : '',
                'time'     => self::$instance->seedEnabled ? json_encode($this->seeder->getTime()) : '',
            ],
            'queries'      => ['list' => [], 'count' => 0, 'duration' => 0],
            'transactions' => ['list' => [], 'count' => 0, 'duration' => 0],
        ];
        
        // Set strict mode settings.
        if ($this->strictEnabled) {
            if ($this->strictForceDeclare) {
                ini_set('zend.strict_types_force_all', 0);
                ini_set('zend.strict_types_force_declare', 1);
            }
            else {
                ini_set('zend.strict_types_force_all', 1);
                ini_set('zend.strict_types_force_declare', 0);
            }
        }
        
        // Log request info to file.
        if ($this->logEnabled) {
            $this->log->text('');
            $this->log->header((PHP_SAPI === 'cli' ? 'CLI #' : 'CGI #') . $this->process->getFullId(true), Log::HEADER_L);
            $this->log->text("Time: {$this->info['request']['date']} / {$this->info['request']['time']}");
            $this->log->text("Seed: {$this->info['seed']['rng']} / {$this->info['seed']['time']}");
        }
        
        if (PHP_SAPI === 'cli') {
            if ($this->logEnabled && !empty($_SERVER['argv'])) {
                $this->log->text('Argv: ' . json_encode($_SERVER['argv']));
            }
        } else {
            if ($this->logEnabled) {
                $this->log->text('Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
                if (!empty($_GET)) $this->log->text('Get: ' . json_encode($_GET));
                if (!empty($_POST)) $this->log->text('Post: ' . json_encode($_POST));
            }
    
            // Intercept all output to be able to print HTTP headers at the end.
            // This only applies to HTTP api, CLI works as usual.
            ob_start();
        }
    }

    /**
     *
     */
    private function append(): void {
        // Close all leftover open logs.
        \PDOLog::shutdown();
        
        // Collect shutdown info.
        $this->info['request']['duration'] = $this->clock->micro(true) - $this->realtime;
        $this->info['queries']['list'] = \PDOLog::getQueries();
        $this->info['queries']['count'] = \PDOLog::getQueriesCount();
        $this->info['queries']['duration'] = \PDOLog::getQueriesTime();
        $this->info['transactions']['list'] = \PDOLog::getTransactions();
        $this->info['transactions']['count'] = \PDOLog::getTransactionsCount();
        $this->info['transactions']['duration'] = \PDOLog::getTransactionsTime();
        
        // Log to file.
        if ($this->logEnabled && $this->statsEnabled) {
            $this->log->header('Stats:', Log::HEADER_M);
            if ($this->statsDuration)     $this->log->text("Duration: {$this->info['request']['duration']} us");
            if ($this->statsQueries)      {
                $text = 'Queries: ' . PHP_EOL
                    . '    Total:' . PHP_EOL
                    . '        Count: ' . $this->info['queries']['count'] . PHP_EOL
                    . '        Duration: ' . $this->info['queries']['duration'] . PHP_EOL
                    . '    List: ' . PHP_EOL;

                foreach ($this->info['queries']['list'] as $q) {
                    $text .= '        ```' . PHP_EOL;
                    $lines = [];
                    $minPadding = -1;
                    foreach (explode(PHP_EOL, $q['query']) as $line) {
                        if (trim($line) === '') continue;
                        preg_match('/^([\s]+?)\S/', $line, $matches);
                        $spaces = isset($matches[1]) ? strlen($matches[1]) : 0;

                        if ($spaces < $minPadding || $minPadding === -1) $minPadding = $spaces;
                        $lines[] = $line;
                    }
                    if ($minPadding < 0) $minPadding = 0;

                    foreach ($lines as $line) {
                        $text .= '          ' . substr($line, $minPadding) . PHP_EOL;
                    }

                    $text .= '        ' . PHP_EOL;
                    $text .= '        Params: ' . json_encode($q['params']) . PHP_EOL;
                    $text .= '        Time:   ' . $q['time'] . ' us' . PHP_EOL;
                    $text .= '        Count:   ' . $q['count'] . PHP_EOL;
                    $text .= '        ' . str_repeat('_', 70) . PHP_EOL;
                }

                $this->log->text(rtrim($text));
            }

            if ($this->statsTransactions) $this->log->text("Transactions: {$this->info['transactions']['count']} / {$this->info['transactions']['duration']} us");
        }

        // Set HTTP headers.
        if (PHP_SAPI === 'cli') return;
    
        if ($this->headersEnabled) {
            header("X-Godlike-R-Id: {$this->info['request']['id']}");
            header("X-Godlike-R-Time: {$this->info['request']['date']} / {$this->info['request']['time']}");
            header("X-Godlike-R-Seed: {$this->info['seed']['rng']} / {$this->info['seed']['time']}");
        
            if ($this->statsEnabled) {
                if ($this->statsDuration)     header("X-Godlike-R-Duration: {$this->info['request']['duration']} us");
                if ($this->statsQueries)      header("X-Godlike-R-Queries: {$this->info['queries']['count']} / {$this->info['queries']['duration']} us");
                if ($this->statsTransactions) header("X-Godlike-R-Transactions: {$this->info['transactions']['count']} / {$this->info['transactions']['duration']} us");
            }
        
            foreach (Logger::getMessages() as $m) header('X-Godlike-U-' . $m);
            foreach (Timer::getMessages() as $m) header('X-Godlike-U-' . $m);
        }
    
        ob_end_flush();
    }
    
    /**
     * @param string $cmd
     * @param array  $params
     *
     * @return array|string
     */
    private function command(string $cmd, array $params = []) {
        // Call init first in case we have not added the prepend script yet.
        $this->init();
     
        // Phpinfo request will exit.
        if ($cmd === 'phpinfo') {
            echo Snitch::phpinfo();
            exit;
        }
    
        // Bootstrap basic error reporting and content type.
        error_reporting(E_ALL | E_STRICT | E_COMPILE_ERROR | E_PARSE);
        ini_set('display_errors', 1);
        header('Content-Type: application/json');
        
        //
        
        if ($cmd === 'reset') {
            if (isset($params['opcache']) && $params['opcache']) opcache_reset();
            if (isset($params['pid']) && $params['pid']) $this->storage->reset('pid');
            if (isset($params['seed']) && $params['seed']) $this->storage->reset('seed');
            if (isset($params['log']) && $params['log']) $this->log->reset();
            
            return 'Environment reset successfully.';
        }
        
        if ($cmd === 'seed') {
            $this->seeder->reset(
                $params['rng'],
                $params['time'],
                $params['timeScale'],
                $params['timeStepMin'],
                $params['timeStepMax']
            );
            
            return 'Environment seeded successfully.';
        }

        if ($cmd === 'config') {
            $this->_config = array_merge($this->_config, $params);
            $ini = '';

            foreach ($this->_config as $k => $v) $ini .= "$k = $v\n";

            file_put_contents($this->configFile, $ini);

            return 'Environment configured successfully.';
        }
        
        return [
            'rng' => Snitch::rng(),
            'time' => Snitch::time(),
            'system' => [
                'environment' => Snitch::env(),
                'ini' => Snitch::ini(),
                'extensions' => Snitch::extensions(),
            ]
        ];
    }

    private static function respond($data = null, \Throwable $error = null): void {
        if ($error) echo json_encode([
            'success' => false,
            'result' => $data,
            'error' => [
                'message' => $error->getMessage(),
                'code' => $error->getCode()
            ]
        ], JSON_PRETTY_PRINT);

        else echo json_encode([
            'success' => true,
            'result' => $data
        ], JSON_PRETTY_PRINT);
    }

    private static function int($a, $null = true): ?int {
        if (is_int($a)) return $a;
        if ($null && ($a === null || (!$a && $a !== '0' && $a !== 0))) return null;
        return (int) $a;
    }

    private static function float($a, $null = true): ?float {
        if (is_float($a)) return $a;
        if ($null && ($a === null || (!$a && $a !== '0' && $a !== 0))) return null;
        return (float) $a;
    }
    
    private static function string($a, $null = true): ?string {
        if (is_string($a) && $a !== null) return $a;
        if ($null && $a === null) return null;
        return (string) $a;
    }
    
    private static function bool($a, $null = true): ?bool {
        if (is_bool($a)) return $a;
        if ($null && ($a === null || (!$a && $a !== '0' && $a !== 0))) return null;
    
        if (is_string($a)) {
            $a = strtolower(trim($a));
            if ($a === '0' || $a === 'false' || $a === 'no') return false;
        }
        
        return (bool) $a;
    }
}
