<?php

namespace Gielfeldt\TransactionalPHP;

/**
 * Class Operation
 *
 * @package Gielfeldt\TransactionalPHP
 */
class Operation
{
    /**
     * @var string[]
     */
    protected $idx;

    /**
     * @var callable
     */
    protected $commit;

    /**
     * @var callable
     */
    protected $rollback;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * Set commit callback.
     *
     * @param callable $callback
     *   The callback when this operation is committed.
     *
     * @return $this
     */
    public function onCommit(callable $callback)
    {
        $this->commit = $callback;
        return $this;
    }

    /**
     * Set rollback callback.
     *
     * @param callable $callback
     *   The callback when this operation is rolled back.
     *
     * @return $this
     */
    public function onRollback(callable $callback)
    {
        $this->rollback = $callback;
        return $this;
    }

    /**
     * Get commit callback.
     *
     * @return callable|null
     */
    public function getCommitCallback()
    {
        return $this->commit;
    }

    /**
     * Get rollback callback.
     *
     * @return callable|null
     */
    public function getRollbackCallback()
    {
        return $this->rollback;
    }

    /**
     * @param mixed $value
     *   The value to set.
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return is_callable($this->value) ? call_user_func($this->value) : $this->value;
    }

    /**
     * Get result from callback.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param Connection $connection
     *   The connection to use for this id.
     * @param string $idx
     *   The id.
     *
     * @return $this
     */
    public function setIdx(Connection $connection, $idx)
    {
        $this->idx[$connection->connectionId()] = $idx;
        return $this;
    }

    /**
     * Get id.
     *
     * @param Connection $connection
     *   The connection to get id from.
     *
     * @return string|null
     */
    public function idx(Connection $connection)
    {
        $connectionId = $connection->connectionId();
        return isset($this->idx[$connectionId]) ? $this->idx[$connectionId] : null;
    }

    /**
     * Execute commit operation.
     *
     * @param Connection $connection
     *   The connection to run this operation on.
     *
     * @return mixed
     */
    public function commit($connection = null)
    {
        return $this->result = $this->commit ? call_user_func($this->commit, $this, $connection) : null;
    }

    /**
     * Execute rollback operation.
     *
     * @param Connection $connection
     *   The connection to run this operation on.
     *
     * @return mixed
     */
    public function rollback($connection = null)
    {
        return $this->result = $this->rollback ? call_user_func($this->rollback, $this, $connection) : null;
    }
}
