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
     * Remove savepoints to and acquire index of latest active savepoint.
     *
     * @param int $oldDepth
     *   The old depth.
     * @param $newDepth
     *   The new depth.
     *
     * @return int|null
     *   The last index, if found.
     */
    public function closeSavepoints($oldDepth, $newDepth)
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
     * {@inheritdoc}
     */
    public function startTransaction($newDepth = null)
    {
        $this->depth = isset($newDepth) ? $newDepth : $this->depth + 1;
        $this->savePoints[$this->depth] = $this->idx;
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction($newDepth = null)
    {
        $oldDepth = $this->depth;
        $this->depth = isset($newDepth) ? $newDepth : $oldDepth - 1;
        if ($this->depth < 0) {
            throw new \RuntimeException('Trying to commit non-existant transaction.');
        }

        // Remove savepoints to and acquire index of latest active savepoint.
        $idx = $this->closeSavepoints($oldDepth, $this->depth);

        // Is this a real commit.
        if ($this->depth == 0 && isset($idx)) {
            // Perform the operations if any found.
            end($this->operations);
            $lastIdx = key($this->operations);
            for ($removeIdx = $idx; $removeIdx <= $lastIdx; $removeIdx++) {
                $this->performOperation($removeIdx);
                $this->removeOperation($removeIdx);
            }
            $this->idx = $idx;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction($newDepth = null)
    {
        $oldDepth = $this->depth;
        $this->depth = isset($newDepth) ? $newDepth : $oldDepth - 1;
        if ($this->depth < 0) {
            throw new \RuntimeException('Trying to rollback non-existant transaction.');
        }

        // Remove savepoints to and acquire index of latest active savepoint.
        $idx = $this->closeSavepoints($oldDepth, $this->depth);

        // Remove operations up until latest active savepoint.
        if (isset($idx)) {
            end($this->operations);
            $lastIdx = key($this->operations);
            for ($removeIdx = $idx; $removeIdx <= $lastIdx; $removeIdx++) {
                $this->removeOperation($removeIdx);
            }
            reset($this->operations);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addOperation(Operation $operation)
    {
        if ($this->depth <= 0) {
            return $operation->execute();
        }

        $idx = $this->idx;
        $this->idx++;
        $this->operations[$idx] = $operation;
        $operation->setIdx($this, $idx);
        return $idx;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation($idx)
    {
        return isset($this->operations[$idx]) ? $this->operations[$idx] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function performOperation($idx)
    {
        return isset($this->operations[$idx]) ? $this->operations[$idx]->execute() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOperation($idx)
    {
        unset($this->operations[$idx]);
    }
}
