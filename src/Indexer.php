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
     * @var int[]
     */
    protected $index = [];

    /**
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
     *   The key to index undeer.
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
     *   The key to index undeer.
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
     * @return array
     *   Operations.
     */
    public function lookup($key)
    {
        return isset($this->index[$key]) ? $this->index[$key] : [];
    }
}
