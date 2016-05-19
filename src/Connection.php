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
    public function startTransaction($new_depth = null)
    {
        $this->depth = isset($new_depth) ? $new_depth : $this->depth + 1;
        $this->savePoints[$this->depth] = $this->idx;
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction($new_depth = null)
    {
        $old_depth = $this->depth;
        $this->depth = isset($new_depth) ? $new_depth : $old_depth - 1;
        if ($this->depth < 0) {
            throw new \RuntimeException('Trying to commit non-existant transaction.');
        }

        // Remove savepoints to and acquire index of latest active savepoint.
        $idx = null;
        for ($depth = $this->depth + 1; $depth <= $old_depth; $depth++) {
            if (isset($this->savePoints[$depth])) {
                $idx = isset($idx) ? $idx : $this->savePoints[$depth];
                unset($this->savePoints[$depth]);
            }
        }

        // Is this a real commit.
        if ($this->depth == 0 && isset($idx)) {
            // Perform the operations if any found.
            end($this->operations);
            $last_idx = key($this->operations);
            for ($remove_idx = $idx; $remove_idx <= $last_idx; $remove_idx++) {
                $this->performOperation($remove_idx);
            }
            $this->idx = $idx;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction($new_depth = null)
    {
        $old_depth = $this->depth;
        $this->depth = isset($new_depth) ? $new_depth : $old_depth - 1;
        if ($this->depth < 0) {
            throw new \RuntimeException('Trying to rollback non-existant transaction.');
        }

        // Remove savepoints to and acquire index of latest active savepoint.
        $idx = null;
        for ($depth = $this->depth + 1; $depth <= $old_depth; $depth++) {
            if (isset($this->savePoints[$depth])) {
                $idx = isset($idx) ? $idx : $this->savePoints[$depth];
                unset($this->savePoints[$depth]);
            }
        }

        // Remove operations up until latest active savepoint.
        if (isset($idx)) {
            end($this->operations);
            $last_idx = key($this->operations);
            for ($remove_idx = $idx; $remove_idx <= $last_idx; $remove_idx++) {
                $this->removeOperation($remove_idx);
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
    public function performOperation($idx, $remove = true)
    {
        $result = null;
        if (isset($this->operations[$idx])) {
            $result = $this->operations[$idx]->execute();
        }
        if ($remove) {
            unset($this->operations[$idx]);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOperation($idx)
    {
        unset($this->operations[$idx]);
    }
}
