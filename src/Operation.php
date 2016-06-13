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
     * @var int[]
     */
    protected $idx;

    /**
     * Callback for commit.
     *
     * @var callable[]
     */
    protected $commit = [];

    /**
     * Callback for rollback.
     *
     * @var callable[]
     */
    protected $rollback = [];

    /**
     * Callback for buffer.
     *
     * @var callable[]
     */
    protected $buffer = [];

    /**
     * Callback for remove.
     *
     * @var callable[]
     */
    protected $remove = [];

    /**
     * Metadata for operation.
     *
     * @var mixed[]
     */
    protected $metadata = [];

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
     * Set rollback callback.
     *
     * @param callable $callback
     *   The callback when this operation is rolled back.
     *
     * @return $this
     */
    public function onRemove(callable $callback)
    {
        $this->remove[] = $callback;
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
     * Get commit callbacks.
     *
     * @return callable|null
     */
    public function getCommitCallbacks()
    {
        return $this->commit;
    }

    /**
     * Get rollback callbacks.
     *
     * @return callable|null
     */
    public function getRollbackCallbacks()
    {
        return $this->rollback;
    }

    /**
     * Get buffer callbacks.
     *
     * @return callable|null
     */
    public function getBufferCallbacks()
    {
        return $this->buffer;
    }

    /**
     * Get remove callbacks.
     *
     * @return callable|null
     */
    public function getRemoveCallbacks()
    {
        return $this->remove;
    }

    /**
     * Set metadata.
     *
     * @param string $key
     *   The key of the metadata.
     * @param mixed $value
     *   The value to set.
     *
     * @return $this
     */
    public function setMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get metadata.
     *
     * @param $key
     *   The key of the metadata to get.
     *
     * @return mixed|null
     */
    public function getMetadata($key)
    {
        return isset($this->metadata[$key]) ? $this->metadata[$key] : null;
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

    /**
     * Execute remove operation.
     *
     * @param Connection $connection
     *   The connection to run this operation on.
     *
     * @return mixed
     */
    public function remove($connection = null)
    {
        foreach ($this->remove as $callback) {
            $this->result = call_user_func($callback, $this, $connection);
        }
        return $this->result;
    }
}
