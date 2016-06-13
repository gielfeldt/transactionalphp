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
     *
     * @var Operation[][]
     */
    protected $index = [];

    /**
     * Operations indexed by this indexer.
     *
     * @var Operation[]
     */
    protected $operations = [];

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
     * @param Operation $operation
     *   The operation to index.
     * @param string $key
     *   (optional) The key to index under.
     *
     * @return Operation
     *   The operation indexed.
     */
    public function index(Operation $operation, $key = null)
    {
        $this->operations[$operation->idx($this->connection)] = $operation;
        if (isset($key)) {
            $this->index[$key][$operation->idx($this->connection)] = $operation;
        }
        $indexer = $this;
        $operation->onRemove(function ($operation) use ($key, $indexer) {
            $indexer->deIndex($operation, $key);
        });
        return $operation;
    }

    /**
     * De-index operation.
     *
     * @param Operation $operation
     *   The operation to index.
     * @param string $key
     *   (optional) The key to index under.
     *
     * @return Operation
     *   The operation de-indexed.
     */
    public function deIndex(Operation $operation, $key = null)
    {
        unset($this->operations[$operation->idx($this->connection)]);
        if (isset($key)) {
            unset($this->index[$key][$operation->idx($this->connection)]);
        }
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
     * @param string $index_key
     *   The index key to look up.
     * @param string $metadata_key
     *   The metadata key to look up.
     *
     * @return array
     *   Values keyed by operation index.
     */
    public function lookupMetadata($index_key, $metadata_key)
    {
        $values = [];
        if (isset($this->index[$index_key])) {
            foreach ($this->index[$index_key] as $operation) {
                $values[$operation->idx($this->connection)] = $operation->getMetadata($metadata_key);
            }
        }
        return $values;
    }

    /**
     * Get current indexed operations.
     *
     * @return Operation[]
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Get current index.
     *
     * @return Operation[][]
     */
    public function getIndex()
    {
        return $this->index;
    }
}
