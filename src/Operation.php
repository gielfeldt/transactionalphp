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
     * The index in the buffer, keyed by connection id.
     *
     * @var string[]
     */
    protected $idx;

    /**
     * Callback for commit.
     *
     * @var callable
     */
    protected $commit = [];

    /**
     * Callback for rollback.
     *
     * @var callable
     */
    protected $rollback = [];

    /**
     * Callback for buffer.
     *
     * @var callable
     */
    protected $buffer = [];

    /**
     * Value for operation.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Result of callback execution.
     *
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
        $this->commit[] = $callback;
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
        $this->rollback[] = $callback;
        return $this;
    }

    /**
     * Set buffer callback.
     *
     * @param callable $callback
     *   The callback when this operation is buffered.
     *
     * @return $this
     */
    public function onBuffer(callable $callback)
    {
        $this->buffer[] = $callback;
        return $this;
    }

    /**
     * Get commit callback.
     *
     * @return callable|null
     */
    public function getCommitCallbacks()
    {
        return $this->commit;
    }

    /**
     * Get rollback callback.
     *
     * @return callable|null
     */
    public function getRollbackCallbacks()
    {
        return $this->rollback;
    }

    /**
     * Get buffer callback.
     *
     * @return callable|null
     */
    public function getBufferCallbacks()
    {
        return $this->buffer;
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
        foreach ($this->commit as $callback) {
            $this->result = call_user_func($callback, $this, $connection);
        }
        return $this->result;
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
        foreach ($this->rollback as $callback) {
            $this->result = call_user_func($callback, $this, $connection);
        }
        return $this->result;
    }

    /**
     * Execute buffer operation.
     *
     * @param Connection $connection
     *   The connection to run this operation on.
     *
     * @return mixed
     */
    public function buffer($connection = null)
    {
        foreach ($this->buffer as $callback) {
            $this->result = call_user_func($callback, $this, $connection);
        }
        return $this->result;
    }
}
