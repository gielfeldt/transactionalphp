<?php

namespace Gielfeldt\TransactionalPHP\Test;

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Operation;

/**
 * @covers \Gielfeldt\TransactionalPHP\Operation
 */
class OperationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider.
     *
     * @return array
     *   Arguments for tests.
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::__construct
     */
    public function operationDataProvider()
    {
        return [[new Operation()]];
    }

    /**
     * Test callback.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::setCallback
     * @covers \Gielfeldt\TransactionalPHP\Operation::getCallback
     */
    public function testCallback(Operation $operation)
    {
        $performed = false;
        $callback = function () use (&$performed) {
            $performed = true;
        };
        $operation->setCallback($callback);

        $check = $operation->getCallback();
        $this->assertSame($callback, $check, 'Correct callback was not set.');
    }

    /**
     * Test value.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::setValue
     * @covers \Gielfeldt\TransactionalPHP\Operation::getValue
     */
    public function testValue(Operation $operation)
    {
        $value = function () {
            return 'myvalue';
        };
        $operation->setValue($value);

        $check = $operation->getValue();
        $this->assertSame('myvalue', $check, 'Correct value was not set.');
    }

    /**
     * Test execute.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::execute
     */
    public function testExecute(Operation $operation)
    {
        $performed = false;
        $callback = function () use (&$performed) {
            $performed = true;
        };
        $operation->setCallback($callback);

        $operation->execute();
        $this->assertTrue($performed, 'Callback was not executed.');
    }

    /**
     * Test id.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::setIdx
     * @covers \Gielfeldt\TransactionalPHP\Operation::idx
     */
    public function testIdx(Operation $operation)
    {
        $connection = new Connection();
        $operation->setIdx($connection, 'testid');

        $check = $operation->idx($connection);
        $this->assertSame('testid', $check, 'Correct id was not set.');
    }
}
