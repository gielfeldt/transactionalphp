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
     * Test commit.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::onCommit
     * @covers \Gielfeldt\TransactionalPHP\Operation::getCommitCallbacks
     * @covers \Gielfeldt\TransactionalPHP\Operation::getResult
     */
    public function testCommit(Operation $operation)
    {
        $performed = false;
        $callback = function () use (&$performed) {
            $performed = true;
            return 'performed';
        };
        $operation->onCommit($callback);

        $check = $operation->getCommitCallbacks();
        $this->assertSame($callback, reset($check), 'Correct callback was not set.');

        $operation->commit();
        $this->assertTrue($performed, 'Callback was not executed.');

        $check = $operation->getResult();
        $this->assertSame('performed', $check, 'Callback did not return proper result.');
    }

    /**
     * Test rollback.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::onRollback
     * @covers \Gielfeldt\TransactionalPHP\Operation::getRollbackCallbacks
     * @covers \Gielfeldt\TransactionalPHP\Operation::getResult
     */
    public function testRollback(Operation $operation)
    {
        $performed = false;
        $callback = function () use (&$performed) {
            $performed = true;
            return 'performed';
        };
        $operation->onRollback($callback);

        $check = $operation->getRollbackCallbacks();
        $this->assertSame($callback, reset($check), 'Correct callback was not set.');

        $operation->rollback();
        $this->assertTrue($performed, 'Callback was not executed.');

        $check = $operation->getResult();
        $this->assertSame('performed', $check, 'Callback did not return proper result.');
    }

    /**
     * Test buffer.
     *
     * @param Operation $operation
     *   The operation to perform tests on.
     *
     * @dataProvider operationDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Operation::onBuffer
     * @covers \Gielfeldt\TransactionalPHP\Operation::getBufferCallbacks
     * @covers \Gielfeldt\TransactionalPHP\Operation::getResult
     */
    public function testBuffer(Operation $operation)
    {
        $performed = false;
        $callback = function () use (&$performed) {
            $performed = true;
            return 'performed';
        };
        $operation->onBuffer($callback);

        $check = $operation->getBufferCallbacks();
        $this->assertSame($callback, reset($check), 'Correct callback was not set.');

        $operation->buffer();
        $this->assertTrue($performed, 'Callback was not executed.');

        $check = $operation->getResult();
        $this->assertSame('performed', $check, 'Callback did not return proper result.');
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
