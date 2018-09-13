<?php


class PDOLog {
    /**
     * @return int
     */
    public static function getTransactionsTime(): int {}
    
    /**
     * @return int
     */
    public static function getTransactionsCount(): int {}
    
    /**
     * @return array
     */
    public static function getTransactions(): array {}
    
    /**
     * @return int
     */
    public static function getQueriesTime(): int {}
    
    /**
     * @return int
     */
    public static function getQueriesCount(): int {}
    
    /**
     * @return array
     */
    public static function getQueries(): array {}
    
    /**
     *
     */
    public static function shutdown(): void {}
    
    /**
     * @param string $pdoId
     *
     * @return PDOLog
     */
    public static function create(string $pdoId): PDOLog {}
    
    /**
     *
     */
    public function close(): void {}
    
    /**
     *
     */
    public function transactionStart(): void {}
    
    /**
     * @param bool $commit
     * @param string|null $error
     */
    public function transactionEnd(bool $commit, ?string $error = null): void {}
    
    /**
     * @param string $statement
     * @param array $params
     * @param bool $prepared
     */
    public function queryStart(string $statement, array $params, bool $prepared): void {}
    
    /**
     * @param string|null $error
     */
    public function queryEnd(?string $error = null): void {}
}
