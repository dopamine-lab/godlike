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
    private $_stmt;

    /** @var PDOLog */
    private $_log;

    /** @var string */
    private $_query;

    /**
     * @param PDOStatement|PDOStatement_original|mixed $stmt
     * @param PDOLog $log
     *
     * @return PDOStatement
     */
    public static function decorate($stmt, PDOLog $log): PDOStatement {
        if (!$stmt) return $stmt; // Skip null statements to avoid null checks everywhere decorate is used.
        if (!($stmt instanceof PDOStatement_original)) {
            throw new \InvalidArgumentException('PDOStatement::decorate expects original PDOStatement instance.');
        }

        $decorator = new PDOStatement();
        $decorator->_stmt = $stmt;
        $decorator->_log = $log;
        $decorator->_query = $stmt->queryString;
        $decorator->queryString = $decorator->_query;

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
            case 1:  return $this->_stmt->setFetchMode($mode);
            case 2:  return $this->_stmt->setFetchMode($mode, $classNameObject);
            default: return $this->_stmt->setFetchMode($mode, $classNameObject, $ctorarfg);
        }
    }

    /**
     * @param int $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value): bool {
        return $this->_stmt->setAttribute($attribute, $value);
    }

    /**
     * @param int $attribute
     *
     * @return mixed
     */
    public function getAttribute($attribute) {
        return $this->_stmt->getAttribute($attribute);
    }


    /**
     * @param array $params
     *
     * @return bool
     */
    public function execute($params = null): bool {
        $this->_log->queryStart($this->_query, $params ?? [], true);

        try {
            if (func_num_args() === 1) $result = $this->_stmt->execute($params);
            else $result = $this->_stmt->execute();
        } catch (\Throwable $e) {}

        $error = $e ?? null;
        $this->_log->queryEnd($error);
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
            case 2:  return $this->_stmt->bindParam($parameter, $variable);
            case 3:  return $this->_stmt->bindParam($parameter, $variable, $data_type);
            case 4:  return $this->_stmt->bindParam($parameter, $variable, $data_type, $length);
            default: return $this->_stmt->bindParam($parameter, $variable, $data_type, $length, $driver_options);
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
            case 2:  return $this->_stmt->bindValue($parameter, $value);
            default: return $this->_stmt->bindValue($parameter, $value, $data_type);
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
            case 2:  return $this->_stmt->bindColumn($column, $param);
            case 3:  return $this->_stmt->bindColumn($column, $param, $type);
            case 4:  return $this->_stmt->bindColumn($column, $param, $type, $maxlen);
            default: return $this->_stmt->bindColumn($column, $param, $type, $maxlen, $driverdata);
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
            case 0:  return $this->_stmt->fetch();
            case 1:  return $this->_stmt->fetch($fetch_style);
            case 2:  return $this->_stmt->fetch($fetch_style, $cursor_orientation);
            default: return $this->_stmt->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        }
    }

    /**
     * @param int $column_number
     *
     * @return mixed
     */
    public function fetchColumn($column_number = 0) {
        return $this->_stmt->fetchColumn($column_number);
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
            case 0:  return $this->_stmt->fetchAll();
            case 1:  return $this->_stmt->fetchAll($fetch_style);
            case 2:  return $this->_stmt->fetchAll($fetch_style, $fetch_argument);
            default: return $this->_stmt->fetchAll($fetch_style, $fetch_argument, $ctor_args);
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
            case 0:  return $this->_stmt->fetchObject();
            case 1:  return $this->_stmt->fetchObject($class_name);
            default: return $this->_stmt->fetchObject($class_name, $ctor_args);
        }
    }

    /**
     * @return int
     */
    public function rowCount(): int {
        return $this->_stmt->rowCount();
    }

    /**
     * @return int
     */
    public function columnCount(): int {
        return $this->_stmt->columnCount();
    }

    /**
     * @param int $column
     *
     * @return array
     */
    public function getColumnMeta($column): array {
        return $this->_stmt->getColumnMeta($column);
    }

    /**
     * @return bool
     */
    public function nextRowset(): bool {
        return $this->_stmt->nextRowset();
    }

    /**
     * @return bool
     */
    public function closeCursor(): bool {
        return $this->_stmt->closeCursor();
    }

    /**
     * @return string
     */
    public function errorCode(): string {
        return $this->_stmt->errorCode();
    }

    /**
     * @return array
     */
    public function errorInfo(): array {
        return $this->_stmt->errorInfo();
    }

    /**
     * @return bool
     */
    public function debugDumpParams(): bool {
        return $this->_stmt->debugDumpParams();
    }
}
