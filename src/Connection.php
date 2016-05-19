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
    protected $id;

    /**
     * Connection constructor.
     *
     * @param null|string $id
     *   (optional) The id of the connection.
     */
    public function __construct($id = null)
    {
        $this->id = isset($id) ? $id : uniqid();
    }

    /**
     * Get connection id.
     *
     * @return null|string
     */
    public function id()
    {
        return $this->id;
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
        $idx = null;
        for ($depth = $this->depth + 1; $depth <= $oldDepth; $depth++) {
            if (isset($this->savePoints[$depth])) {
                $idx = isset($idx) ? $idx : $this->savePoints[$depth];
                unset($this->savePoints[$depth]);
            }
        }

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
        $idx = null;
        for ($depth = $this->depth + 1; $depth <= $oldDepth; $depth++) {
            if (isset($this->savePoints[$depth])) {
                $idx = isset($idx) ? $idx : $this->savePoints[$depth];
                unset($this->savePoints[$depth]);
            }
        }

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
        if ($this->depth > 0) {
            $idx = $this->idx;
            $this->idx++;
            $this->operations[$idx] = $operation;
            $operation->setId($this, $idx);
            return $idx;
        } else {
            return $operation->execute();
        }
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
