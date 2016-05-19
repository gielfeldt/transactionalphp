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
     * Index operation.
     *
     * @param string $key
     *   The key to index undeer.
     * @param Operation|null $operation
     *   The operation to index.
     */
    public function index($key, Operation $operation = null)
    {
        if ($operation) {
            $this->index[$key][] = $operation;
        }
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
