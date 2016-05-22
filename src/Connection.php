<?php

namespace Gielfeldt\TransactionalPHP;

/**
 * Class Connection
 *
 * @package Gielfeldt\TransactionalPHP
 */
class Connection
{
    /**
     * @var Operation[]
     */
    protected $operations = [];

    /**
     * @var int
     */
    protected $idx = 0;

    /**
     * @var int[]
     */
    protected $savePoints = [];

    /**
     * @var int
     */
    protected $depth = 0;

    /**
     * @var null|string
     */
    protected $connectionId;

    /**
     * Connection constructor.
     *
     * @param null|string $connectionId
     *   (optional) The id of the connection.
     */
    public function __construct($connectionId = null)
    {
        $this->connectionId = isset($connectionId) ? $connectionId : uniqid();
    }

    /**
     * Get connection id.
     *
     * @return null|string
     */
    public function connectionId()
    {
        return $this->connectionId;
    }

    /**
     * Get current depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Remove save points to and acquire index of latest active savepoint.
     *
     * @param int $oldDepth
     *   The old depth.
     * @param int $newDepth
     *   The new depth.
     *
     * @return int
     *   The index of the last open save point.
     */
    protected function closeSavePoints($oldDepth, $newDepth)
    {
        $idx = null;
        for ($depth = $newDepth + 1; $depth <= $oldDepth; $depth++) {
            if (isset($this->savePoints[$depth])) {
                $idx = isset($idx) ? $idx : $this->savePoints[$depth];
                unset($this->savePoints[$depth]);
            }
        }
        return $idx;
    }

    /**
     * Collection operations from the specified index.
     *
     * @param int $idx
     *   The starting index.
     *
     * @return Operation[]
     *   The operations from the specified index (included)
     */
    protected function collectOperations($idx)
    {
        // Collect the operations.
        $operations = [];
        end($this->operations);
        $lastIdx = key($this->operations);
        for ($removeIdx = $idx; $removeIdx <= $lastIdx; $removeIdx++) {
            if (isset($this->operations[$removeIdx])) {
                $operations[$removeIdx] = $this->operations[$removeIdx];
            }
        }
        reset($this->operations);
        return $operations;
    }

    /**
     * Run commit on operations and remove them from the buffer.
     *
     * @param Operation[] $operations
     *   The operations to commit.
     */
    protected function commitOperations($operations)
    {
        foreach ($operations as $operation) {
            $operation->commit($this);
            $this->removeOperation($operation);
        }
    }

    /**
     * Run commit on operations and remove them from the buffer.
     *
     * @param Operation[] $operations
     *   The operations to commit.
     */
    protected function rollbackOperations($operations)
    {
        foreach ($operations as $operation) {
            $operation->rollback($this);
            $this->removeOperation($operation);
        }
    }

    /**
     * Start transaction.
     *
     * @param int $newDepth
     *   (optional) If specified, use as new depth, otherwise increment current depth.
     *
     */
    public function startTransaction($newDepth = null)
    {
        $this->depth = isset($newDepth) ? $newDepth : $this->depth + 1;
        $this->savePoints[$this->depth] = $this->idx;
    }

    /**
     * Commit transaction.
     *
     * @param int $newDepth
     *   (optional) If specified, use as new depth, otherwise decrement current depth.
     *
     */
    public function commitTransaction($newDepth = null)
    {
        $oldDepth = $this->depth;
        $this->depth = isset($newDepth) ? $newDepth : $oldDepth - 1;
        if ($this->depth < 0) {
            throw new \RuntimeException('Trying to commit non-existant transaction.');
        }

        // Close save points and acquire last known open index.
        $idx = $this->closeSavePoints($oldDepth, $this->depth);

        // Is this a real commit.
        if ($this->depth == 0 && isset($idx)) {
            $operations = $this->collectOperations($idx);
            $this->commitOperations($operations);
        }
    }

    /**
     * Rollback transaction.
     *
     * @param int $newDepth
     *   (optional) If specified, use as new depth, otherwise decrement current depth.
     *
     */
    public function rollbackTransaction($newDepth = null)
    {
        $oldDepth = $this->depth;
        $this->depth = isset($newDepth) ? $newDepth : $oldDepth - 1;
        if ($this->depth < 0) {
            throw new \RuntimeException('Trying to rollback non-existant transaction.');
        }

        // Close save points and acquire last known open index.
        $idx = $this->closeSavePoints($oldDepth, $this->depth);
        $operations = $this->collectOperations($idx);
        $this->rollbackOperations($operations);
    }

    /**
     * Add operation.
     *
     * @param Operation $operation
     *   The operation to add to the connection.
     *
     * @return Operation
     *   The operation added.
     */
    public function addOperation(Operation $operation)
    {
        if ($this->depth <= 0) {
            $operation->commit();
            return $operation;
        }
        $idx = $this->idx;
        $this->idx++;
        $this->operations[$idx] = $operation;
        $operation->setIdx($this, $idx);
        return $operation;
    }

    /**
     * Check if the connection has an operation.
     *
     * @param Operation $operation
     *   The operation to check for.
     *
     * @return bool
     *   TRUE if the operation exists.
     */
    public function hasOperation(Operation $operation)
    {
        return isset($this->operations[$operation->idx($this)]);
    }

    /**
     * Remove operation.
     *
     * @param Operation $operation
     *   The operation to remove from the connection.
     */
    public function removeOperation(Operation $operation)
    {
        unset($this->operations[$operation->idx($this)]);
    }

    /**
     * Short-hand notation for adding code to be run on commit.
     *
     * @param callable $callback
     *   The code to run on commit.
     *
     * @return Operation
     *   The operation created.
     */
    public function onCommit(callable $callback)
    {
        return $this->addOperation((new Operation())
            ->onCommit($callback));
    }

    /**
     * Short-hand notation for adding code to be run on rollback.
     *
     * @param callable $callback
     *   The code to run on rollback.
     *
     * @return Operation
     *   The operation created.
     */
    public function onRollback(callable $callback)
    {
        return $this->addOperation((new Operation())
            ->onRollback($callback));
    }

    /**
     * Short-hand notation for adding code to be run on rollback.
     *
     * @param mixed $value
     *   The value to add.
     *
     * @return Operation
     *   The operation created.
     */
    public function addValue($value)
    {
        return $this->addOperation((new Operation())
            ->setValue($value));
    }
}
