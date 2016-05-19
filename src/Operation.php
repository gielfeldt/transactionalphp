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
     * @var string[]
     */
    protected $idx;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Set callback.
     *
     * @param callable $callback
     *   The callback.
     *
     * @return $this
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Get callback.
     *
     * @return callable|null
     */
    public function getCallback()
    {
        return $this->callback;
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
     * Execute operation.
     *
     * @return mixed
     */
    public function execute()
    {
        return call_user_func($this->callback);
    }
}
