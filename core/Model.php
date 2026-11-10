<?php
// =====================================================
// MODEL (base class)
// A Model is the only place that talks to the database.
// Every model in the models/ folder "extends" this class,
// so it gets these small helpers for free:
//
//   $this->getRow($sql, $params)     -> one row (or false if none)
//   $this->getRows($sql, $params)    -> a list of rows
//   $this->getValue($sql, $params)   -> a single value, like a COUNT
//   $this->getColumn($sql, $params)  -> a list of single values, like ids
//   $this->run($sql, $params)        -> just run it (INSERT / UPDATE)
//
// The ? marks in the SQL are filled from $params (this keeps the site safe).
// =====================================================
class Model
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    protected function run($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function getRow($sql, $params = [])
    {
        return $this->run($sql, $params)->fetch();
    }

    protected function getRows($sql, $params = [])
    {
        return $this->run($sql, $params)->fetchAll();
    }

    protected function getValue($sql, $params = [])
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    protected function getColumn($sql, $params = [])
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    // Create the next code, like R011 or B007
    // $table and $column are written by us in the code, never typed by a user.
    protected function nextCode($table, $column, $prefix, $digits)
    {
        $start = strlen($prefix) + 1;
        $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING($column, $start) AS UNSIGNED)), 0) FROM $table";
        $max = (int)$this->getValue($sql);
        return $prefix . str_pad($max + 1, $digits, '0', STR_PAD_LEFT);
    }
}
