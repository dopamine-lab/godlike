<?php

/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUndefinedClassConstantInspection */
/** @noinspection PhpUndefinedClassInspection */

require_once __DIR__ . '/PDO.php';
require_once __DIR__ . '/PDOLog.php';

if (class_exists('PDOStatement')) return;


class PDOStatement {
    /** @var string */
    public $queryString;

    /** @var \PDOStatement|PDOStatement_original */
    private $__stmt;

    /** @var PDO */
    private $__pdo;

    /** @var PDOLog */
    private $__log;

    /** @var string */
    private $__query;
    
    /**
     * @param PDOStatement|PDOStatement_original|mixed $stmt
     * @param PDO $pdo
     * @param PDOLog $log
     *
     * @return PDOStatement
     */
    public static function decorate($stmt, PDO $pdo, PDOLog $log): PDOStatement {
        if (!$stmt) return $stmt; // Skip null statements to avoid null checks everywhere decorate is used.
        if (!($stmt instanceof PDOStatement_original)) {
            throw new \InvalidArgumentException('PDOStatement::decorate expects original PDOStatement instance.');
        }

        $decorator = new PDOStatement();
        $decorator->__pdo = $pdo;
        $decorator->__stmt = $stmt;
        $decorator->__log = $log;
        $decorator->__query = $stmt->queryString;
        $decorator->queryString = $decorator->__query;

        return $decorator;
    }

    /**
     *
     */
    private function __construct() {
        // Lets make this private to make sure no one tries to call PDOStatement outside of PDO::prepare or PDO::query.

        // Native PDOStatement is buggy when default values are passed instead of no arguments.
        // That's why we use switch statements in all overridden methods with optional args.
    }

    /**
     * @param int $mode
     * @param int|string|object $classNameObject
     * @param array $ctorarfg
     *
     * @return bool
     */
    public function setFetchMode($mode, $classNameObject = null, $ctorarfg = null): bool {
        switch (func_num_args()) {
            case 1:  return $this->__stmt->setFetchMode($mode);
            case 2:  return $this->__stmt->setFetchMode($mode, $classNameObject);
            default: return $this->__stmt->setFetchMode($mode, $classNameObject, $ctorarfg);
        }
    }

    /**
     * @param int $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value): bool {
        return $this->__stmt->setAttribute($attribute, $value);
    }

    /**
     * @param int $attribute
     *
     * @return mixed
     */
    public function getAttribute($attribute) {
        return $this->__stmt->getAttribute($attribute);
    }


    /**
     * @param array $params
     *
     * @return bool
     */
    public function execute($params = null): bool {
        /** @noinspection PhpDeprecationInspection */
        $this->__pdo->__setGodlikeTimestamp();
        $this->__log->queryStart($this->__query, $params ?? [], true);

        try {
            if (func_num_args() === 1) $result = $this->__stmt->execute($params);
            else $result = $this->__stmt->execute();
        } catch (\Throwable $e) {}

        $error = $e ?? null;
        $this->__log->queryEnd($error);
        if ($error) throw $error;

        return $result ?? false;
    }

    /**
     * @param mixed $parameter
     * @param mixed $variable
     * @param int $data_type
     * @param int $length
     * @param mixed $driver_options
     *
     * @return bool
     */
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null): bool {
        switch (func_num_args()) {
            case 2:  return $this->__stmt->bindParam($parameter, $variable);
            case 3:  return $this->__stmt->bindParam($parameter, $variable, $data_type);
            case 4:  return $this->__stmt->bindParam($parameter, $variable, $data_type, $length);
            default: return $this->__stmt->bindParam($parameter, $variable, $data_type, $length, $driver_options);
        }
    }

    /**
     * @param mixed $parameter
     * @param mixed $value
     * @param int $data_type
     *
     * @return bool
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR): bool {
        /** @noinspection DegradedSwitchInspection */
        switch (func_num_args()) {
            case 2:  return $this->__stmt->bindValue($parameter, $value);
            default: return $this->__stmt->bindValue($parameter, $value, $data_type);
        }
    }

    /**
     * @param mixed $column
     * @param mixed $param
     * @param int $type
     * @param int $maxlen
     * @param mixed $driverdata
     *
     * @return bool
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null): bool {
        switch (func_num_args()) {
            case 2:  return $this->__stmt->bindColumn($column, $param);
            case 3:  return $this->__stmt->bindColumn($column, $param, $type);
            case 4:  return $this->__stmt->bindColumn($column, $param, $type, $maxlen);
            default: return $this->__stmt->bindColumn($column, $param, $type, $maxlen, $driverdata);
        }
    }

    /**
     * @param int $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     *
     * @return mixed
     */
    public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        switch (func_num_args()) {
            case 0:  return $this->__stmt->fetch();
            case 1:  return $this->__stmt->fetch($fetch_style);
            case 2:  return $this->__stmt->fetch($fetch_style, $cursor_orientation);
            default: return $this->__stmt->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        }
    }

    /**
     * @param int $column_number
     *
     * @return mixed
     */
    public function fetchColumn($column_number = 0) {
        return $this->__stmt->fetchColumn($column_number);
    }

    /**
     * @param int $fetch_style
     * @param mixed $fetch_argument
     * @param array $ctor_args
     *
     * @return array
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = null): array {
        switch (func_num_args()) {
            case 0:  return $this->__stmt->fetchAll();
            case 1:  return $this->__stmt->fetchAll($fetch_style);
            case 2:  return $this->__stmt->fetchAll($fetch_style, $fetch_argument);
            default: return $this->__stmt->fetchAll($fetch_style, $fetch_argument, $ctor_args);
        }
    }

    /**
     * @param string $class_name
     * @param array $ctor_args
     *
     * @return mixed
     */
    public function fetchObject($class_name = null, $ctor_args = null) {
        switch (func_num_args()) {
            case 0:  return $this->__stmt->fetchObject();
            case 1:  return $this->__stmt->fetchObject($class_name);
            default: return $this->__stmt->fetchObject($class_name, $ctor_args);
        }
    }

    /**
     * @return int
     */
    public function rowCount(): int {
        return $this->__stmt->rowCount();
    }

    /**
     * @return int
     */
    public function columnCount(): int {
        return $this->__stmt->columnCount();
    }

    /**
     * @param int $column
     *
     * @return array
     */
    public function getColumnMeta($column): array {
        return $this->__stmt->getColumnMeta($column);
    }

    /**
     * @return bool
     */
    public function nextRowset(): bool {
        return $this->__stmt->nextRowset();
    }

    /**
     * @return bool
     */
    public function closeCursor(): bool {
        return $this->__stmt->closeCursor();
    }

    /**
     * @return string
     */
    public function errorCode(): string {
        return $this->__stmt->errorCode();
    }

    /**
     * @return array
     */
    public function errorInfo(): array {
        return $this->__stmt->errorInfo();
    }

    /**
     * @return bool
     */
    public function debugDumpParams(): bool {
        return $this->__stmt->debugDumpParams();
    }
}
