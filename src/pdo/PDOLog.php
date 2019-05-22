<?php

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUndefinedClassConstantInspection */
/** @noinspection PhpUndefinedClassInspection */

require_once __DIR__ . '/PDO.php';
require_once __DIR__ . '/PDOStatement.php';


class PDOLog {
    //
    // Static global PDO log.

    /** @var PDOLog[] */
    private static $instances = [];

    /** @var int */
    private static $transactionId = 1;
    
    /** @var array */
    private static $transactions = [];
    
    /** @var int */
    private static $queryId = 1;
    
    /** @var array */
    private static $queries = [];

    /** @var int */
    private static $transactionsTime = 0;

    /** @var int */
    private static $transactionsCount = 0;

    /** @var int */
    private static $queriesTime = 0;

    /** @var int */
    private static $queriesCount = 0;

    /**
     * @return int
     */
    public static function getTransactionsTime(): int {
        return self::$transactionsTime;
    }

    /**
     * @return int
     */
    public static function getTransactionsCount(): int {
        return self::$transactionsCount;
    }

    /**
     * @return array
     */
    public static function getTransactions(): array {
        return self::$transactions;
    }

    /**
     * @return int
     */
    public static function getQueriesTime(): int {
        return self::$queriesTime;
    }

    /**
     * @return int
     */
    public static function getQueriesCount(): int {
        return self::$queriesCount;
    }

    /**
     * @return array
     */
    public static function getQueries(): array {
        return self::$queries;
    }

    /**
     *
     */
    public static function shutdown(): void {
        // This must be called on shutdown in order to close all open hanging transactions or queries.
        foreach (self::$instances as $log) $log->close();
    }

    /**
     * @param string $pdoId
     *
     * @return PDOLog
     */
    public static function create(string $pdoId): PDOLog {
        $log = new self($pdoId);
        self::$instances[] = $log;

        return $log;
    }

    /**
     * @return int
     */
    private static function time(): int {
        return __godlike_timestamp_cache(true)[0];
    }
    
    //
    // Local connection PDO log.
    
    /** @var string */
    private $pdoId;
    
    /** @var int */
    private $tId;
    
    /** @var int */
    private $qId;

    /**
     * @param string $pdoId
     */
    private function __construct(string $pdoId) {
        $this->pdoId = $pdoId;
    }

    /**
     *
     */
    public function close(): void {
        // There must be NO open queries on shutdown!
        if ($this->qId) throw new LogicException('PDOLog::transactionStart called before closing the previous query.');

        // End the transaction if there is one.
        if ($this->tId) $this->transactionEnd(false);
    }

    /**
     *
     */
    public function transactionStart(): void {
        // If there is already an open query, throw error, because this is never allowed and is obviously a bug.
        if ($this->qId) throw new LogicException('PDOLog::transactionStart called before closing the previous query.');
        
        // If there is already an open transaction, just leave it be. MySQL allows this behavior, we do too.
        if ($this->tId) return;

        $this->tId = self::$transactionId ++;

        // Add transaction to global log early to preserve chronology.
        self::$transactions[$this->tId] = [
            'id' => $this->tId,
            'pdoId' => $this->pdoId,
            'commit' => null,
            'error' => null,
            'timeStart' => self::time(),
            'timeEnd' => null,
            'time' => null,
            
            // The queries array will be deleted later.
            'queries' => []
        ];
    }

    /**
     * @param bool $commit
     * @param string|null $error
     */
    public function transactionEnd(bool $commit, ?string $error = null): void {
        // If there is already an open query, throw error, because this is never allowed and is obviously a bug.
        if ($this->qId) throw new LogicException('PDOLog::transactionEnd called before closing the previous query.');
        
        // If there is no open transaction, just leave it be. MySQL allows this behavior, we do too.
        if (!$this->tId) return;

        // Take a reference for performance. We don't want to hurt the application we are testing.
        $transaction = &self::$transactions[$this->tId];

        // Log time and stuff.
        $transaction['commit'] = $commit;
        $transaction['error'] = $error;
        $transaction['timeEnd'] = self::time();
        $transaction['time'] = $transaction['timeEnd'] - $transaction['timeStart'];

        // Set the commit property to all sub-queries.
        foreach ($transaction['queries'] as $qId) {
            self::$queries[$qId]['commit'] = $commit;
        }

        // Log only query count in the end for all transactions.
        $transaction['queries'] = count($transaction['queries']);

        // Count global stats.
        self::$transactionsCount ++;
        self::$transactionsTime += $transaction['time'];

        // Finally, reset.
        $this->tId = null;
    }

    /**
     * @param string $statement
     * @param array $params
     * @param bool $prepared
     */
    public function queryStart(string $statement, array $params, bool $prepared): void {
        // If there is already an open query, throw error, because this is never allowed and is obviously a bug.
        if ($this->qId) throw new LogicException('PDOLog::queryStart called before closing the previous query.');

        $this->qId = self::$queryId ++;
    
        self::$queries[$this->qId] = [
            'id' => $this->qId,
            'pdoId' => $this->pdoId,
            'query' => $statement,
            'params' => $params,
            'prepared' => $prepared,
            'transaction' => $this->tId,
            'commit' => null,
            'error' => null,
            'timeStart' => self::time(),
            'timeEnd' => null,
            'time' => null,
            'count' => 1
        ];
        $currentParams = $params;
        usort($currentParams, function($a, $b) {return (string) $a <=> (string) $b;});
        $currentParamsStr = json_encode($currentParams);

        foreach (self::$queries as $id => $q) {
            if (trim($q['query']) === trim($statement)) {
                usort($q['params'], function($a, $b) {return (string) $a <=> (string) $b;});
                if ($id !== $this->qId && $currentParamsStr === json_encode($q['params'])) {
                    self::$queries[$this->qId]['count']++;
                    self::$queries[$id]['count']++;
                }
            }
        }

        // If the query is in transaction we need to do some work afterwards to set the commit and transaction property.
        if ($this->tId) self::$transactions[$this->tId]['queries'][] = $this->qId;
    }

    /**
     * @param string|null $error
     */
    public function queryEnd(?string $error = null): void {
        // If there is no open query, throw error, because this is never allowed and is obviously a bug.
        if (!$this->qId) throw new LogicException('PDOLog::queryEnd called without a started query.');

        // Take a reference for performance. We don't want to hurt the application we are testing.
        $query = &self::$queries[$this->qId];

        // Log time and stuff.
        $query['error'] = $error;
        $query['timeEnd'] = self::time();
        $query['time'] = $query['timeEnd'] - $query['timeStart'];

        // Count global stats.
        self::$queriesCount ++;
        self::$queriesTime += $query['time'];

        // Finally, reset.
        $this->qId = null;
    }
}
