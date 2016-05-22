<?php

namespace Gielfeldt\TransactionalPHP;

/**
 * Class Indexer
 *
 * @package Gielfeldt\TransactionalPHP
 */
class Indexer
{
    /**
     * Operations indexed by key and operation index.

     * @var Operation[][]
     */
    protected $index = [];

    /**
     * The connection used by this indexer.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Indexer constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Index operation.
     *
     * @param string $key
     *   The key to index under.
     * @param Operation $operation
     *   The operation to index.
     *
     * @return Operation
     *   The operation indexed.
     */
    public function index($key, Operation $operation)
    {
        $this->index[$key][$operation->idx($this->connection)] = $operation;
        $indexer = $this;
        $operation->onCommit(function ($operation) use ($key, $indexer) {
            $indexer->deIndex($key, $operation);
        });
        $operation->onRollback(function ($operation) use ($key, $indexer) {
            $indexer->deIndex($key, $operation);
        });
        return $operation;
    }

    /**
     * De-index operation.
     *
     * @param string $key
     *   The key to index under.
     * @param Operation $operation
     *   The operation to index.
     *
     * @return Operation
     *   The operation de-indexed.
     */
    public function deIndex($key, Operation $operation)
    {
        unset($this->index[$key][$operation->idx($this->connection)]);
        return $operation;
    }

    /**
     * Lookup operation.
     *
     * @param string $key
     *   The key to look up.
     *
     * @return Operation[]
     *   Operations keyed by operation index.
     */
    public function lookup($key)
    {
        return isset($this->index[$key]) ? $this->index[$key] : [];
    }

    /**
     * Lookup operation values.
     *
     * @param string $key
     *   The key to look up.
     *
     * @return array
     *   Values keyed by operation index.
     */
    public function lookupValues($key)
    {
        $values = [];
        if (isset($this->index[$key])) {
            foreach ($this->index[$key] as $operation) {
                $values[$operation->idx($this->connection)] = $operation->getValue();
            }
        }
        return $values;
    }
}
