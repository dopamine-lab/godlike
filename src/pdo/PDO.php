<?php

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUndefinedClassConstantInspection */
/** @noinspection PhpUndefinedClassInspection */

require_once __DIR__ . '/PDOStatement.php';
require_once __DIR__ . '/PDOLog.php';

if (class_exists('PDO')) return;


class PDO extends PDO_original {
    /** @var int */
    private static $__connectionId = 1;
    
    /** @var int */
    private static $__timestamp;
    
    /** @var string */
    private $__dsn;
    
    /** @var string */
    private $__user;
    
    /**
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array|null  $options
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null) {
        if ($options && isset($options[PDO::ATTR_ERRMODE]) && $options[PDO::ATTR_ERRMODE] !== PDO::ERRMODE_EXCEPTION) {
            throw new \LogicException('This error mode is not supported. Please use only ERRMODE_EXCEPTION.');
        }

        $id = self::$__connectionId ++;
        
        // Save these variables to add them to the query log later.
        $this->__dsn = $dsn;
        $this->__user = $username;

        // Create local query/transaction logger.
        $this->_log = PDOLog::create("$id/$dsn/$username");

        // Native PDO is buggy when default values are passed instead of no arguments.
        // That's why we use switch statements in all overridden methods with optional args.
        switch (func_num_args()) {
            case 1:  parent::__construct($dsn); break;
            case 2:  parent::__construct($dsn, $username); break;
            case 3:  parent::__construct($dsn, $username, $password); break;
            default: parent::__construct($dsn, $username, $password, $options); break;
        }
    }
    
    /**
     * @deprecated
     */
    public function __setGodlikeTimestamp(): void {
        $t = $_SERVER['GODLIKE_TIMESTAMP'] ?? null;
        if ($t === self::$__timestamp) return;
        self::$__timestamp = $t;
        
        $timestamp = 0;
        if (self::$__timestamp !== null) {
            $timestamp = \bcdiv(self::$__timestamp, '1000', 6);
        }
    
        parent::exec('SET timestamp = ' . $timestamp);
    }
    
    /**
     * @return bool
     */
    public function beginTransaction(): bool {
        $this->_log->transactionStart();

        try {
            $result = parent::beginTransaction();
        } catch (\Throwable $error) {
            $this->_log->transactionEnd(false, $error);
            throw $error;
        }

        return $result;
    }
    
    /**
     * @return bool
     */
    public function commit(): bool {
        try {
            $result = parent::commit();
        } catch (\Throwable $error) {
            $this->_log->transactionEnd(false, $error);
            throw $error;
        }

        $this->_log->transactionEnd(true);

        return $result;
    }
    
    /**
     * @return bool
     */
    public function rollBack(): bool {
        try {
            $result = parent::rollBack();
        } catch (\Throwable $error) {
            $this->_log->transactionEnd(false, $error);
            throw $error;
        }

        $this->_log->transactionEnd(false);

        return $result;
    }

    /**
     * @param string $statement
     *
     * @return int
     */
    public function exec($statement): int {
        $this->__setGodlikeTimestamp();
        $this->_log->queryStart($statement, [], false);

        try {
            $result = parent::exec($statement);
        } catch (\Throwable $e) {}

        $error = $e ?? null;
        $this->_log->queryEnd($error);
        if ($error) throw $error;

        return $result ?? 0;
    }
    
    /**
     * @param string $statement
     * @param int    $mode
     * @param mixed  $arg3
     * @param mixed  $arg4
     *
     * @return PDOStatement
     */
    public function query($statement, $mode = PDO::FETCH_ASSOC, $arg3 = null, $arg4 = null): PDOStatement {
        /** @noinspection PhpDeprecationInspection */
        $this->__setGodlikeTimestamp();
        $this->_log->queryStart($statement, [], false);

        try {
            if ($arg3 === null && $arg4 === null) $result = parent::query($statement, $mode);
            elseif ($arg4 === null) $result = parent::query($statement, $mode, $arg3);
            elseif ($arg3 === null) $result = parent::query($statement, $mode, null, $arg4);
            else $result = parent::query($statement, $mode, $arg3, $arg4);
        } catch (\Throwable $e) {}

        $error = $e ?? null;
        $this->_log->queryEnd($error);
        if ($error) throw $error;

        return (isset($result) && $result) ? PDOStatement::decorate($result, $this, $this->_log) : null;
    }
    
    /**
     * @param string $statement
     * @param array  $options
     *
     * @return PDOStatement
     */
    public function prepare($statement, $options = null): PDOStatement {
        if ($options && isset($options[PDO::ATTR_ERRMODE]) && $options[PDO::ATTR_ERRMODE] !== PDO::ERRMODE_EXCEPTION) {
            throw new \LogicException('This error mode is not supported. Please use only ERRMODE_EXCEPTION.');
        }

        if ($options === null) $result = parent::prepare($statement);
        else $result = parent::prepare($statement, $options);

        return (isset($result) && $result) ? PDOStatement::decorate($result, $this, $this->_log) : null;
    }
    
    /**
     * @return string
     */
    public function getDsn(): string {
        return $this->__dsn;
    }
    
    /**
     * @return string
     */
    public function getUser(): string {
        return $this->__user;
    }

    /**
     * @param int $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value): bool {
        if ($attribute === PDO::ATTR_ERRMODE && $value !== PDO::ERRMODE_EXCEPTION) {
            throw new \LogicException('This error mode is not supported. Please use only ERRMODE_EXCEPTION.');
        }

        return parent::setAttribute($attribute, $value);
    }
}
