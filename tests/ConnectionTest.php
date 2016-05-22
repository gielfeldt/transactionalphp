<?php

namespace Gielfeldt\TransactionalPHP\Test;

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Operation;

/**
 * @covers \Gielfeldt\TransactionalPHP\Operation
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider.
     *
     * @return array
     *   Arguments for tests.
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::__construct
     */
    public function connectionDataProvider()
    {
        return [[new Connection('testid')]];
    }

    /**
     * Test setup.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::__construct
     * @covers \Gielfeldt\TransactionalPHP\Connection::connectionId
     */
    public function testSetup(Connection $connection)
    {
        $id = $connection->connectionId();
        $this->assertEquals('testid', $id, 'ID was not properly set.');

        $connection = new Connection('testid2');
        $id = $connection->connectionId();
        $this->assertEquals('testid2', $id, 'ID was not properly set.');
    }

    /**
     * Test add operation.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::addOperation
     * @covers \Gielfeldt\TransactionalPHP\Connection::startTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::setDepth
     */
    public function testAddOperation(Connection $connection)
    {
        $operation = new Operation();
        $operation->onCommit(function () {
            return 'testresult';
        });
        $connection->addOperation($operation);

        $this->assertNull($operation->idx($connection), 'Operation was not properly added.');
        #var_dump($operation->idx($connection));

        $connection->startTransaction();
        $connection->addOperation($operation);

        $this->assertNotNull($operation->idx($connection), 'Operation was not properly added.');
    }

    /**
     * Test commit.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::onCommit
     * @covers \Gielfeldt\TransactionalPHP\Connection::hasOperation
     */
    public function testOnCommit(Connection $connection)
    {
        $callback = function () {
            return 'testresult';
        };
        $operation = $connection->onCommit($callback);

        $this->assertFalse($connection->hasOperation($operation), 'Operation was not properly added.');

        $connection->startTransaction();
        $operation = $connection->onCommit($callback);

        $this->assertTrue($connection->hasOperation($operation), 'Operation was not properly added.');
        $connection->commitTransaction();
        $check = $operation->getResult();
        $this->assertSame('testresult', $check, 'Operation was not properly added.');
    }

    /**
     * Test rollback.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::onRollback
     * @covers \Gielfeldt\TransactionalPHP\Connection::hasOperation
     */
    public function testOnRollback(Connection $connection)
    {
        $callback = function () {
            return 'testresult';
        };
        $operation = $connection->onRollback($callback);

        $this->assertFalse($connection->hasOperation($operation), 'Operation was not properly added.');

        $connection->startTransaction();
        $operation = $connection->onRollback($callback);

        $this->assertTrue($connection->hasOperation($operation), 'Operation was not properly added.');
        $connection->rollbackTransaction();
        $check = $operation->getResult();
        $this->assertSame('testresult', $check, 'Operation was not properly added.');
    }

    /**
     * Test add value.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::addValue
     */
    public function testAddValue(Connection $connection)
    {
        $callback = function () {
            return 'testresult';
        };
        $operation = $connection->addValue($callback);

        $check = $operation->getValue();
        $this->assertSame('testresult', $check, 'Operation was not properly added.');
    }

    /**
     * Test remove operation.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::removeOperation
     */
    public function testRemoveOperation(Connection $connection)
    {
        $operation = new Operation();
        $operation->onCommit(function () {
            return 'testresult';
        });

        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->removeOperation($operation);

        $check = $connection->hasOperation($operation);
        $this->assertFalse($check, 'Operation was not properly removed.');
    }

    /**
     * Test commit transaction.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::closeSavePoints
     * @covers \Gielfeldt\TransactionalPHP\Connection::collectOperations
     * @covers \Gielfeldt\TransactionalPHP\Connection::commitTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::commitOperations
     */
    public function testCommitTransaction(Connection $connection)
    {
        $operation = new Operation();
        $operation->onCommit(function () use (&$committed) {
            $committed = true;
        });
        $operation->onRollback(function () use (&$rolledback) {
            $rolledback = true;
        });

        $committed = false;
        $rolledback = false;
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->commitTransaction();

        $this->assertTrue($committed, 'Commit was not performed.');
        $this->assertFalse($rolledback, 'Rollback was performed.');

        $committed = false;
        $rolledback = false;
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->commitTransaction();
        $connection->commitTransaction();

        $this->assertTrue($committed, 'Commit was not performed.');
        $this->assertFalse($rolledback, 'Rollback was performed.');
    }

    /**
     * Test commit exception.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::commitTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::setDepth
     *
     * @expectedException \RuntimeException
     */
    public function testCommitException(Connection $connection)
    {
        $connection->startTransaction();
        $connection->commitTransaction();
        $connection->commitTransaction();
    }

    /**
     * Test rollback transaction.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::closeSavePoints
     * @covers \Gielfeldt\TransactionalPHP\Connection::collectOperations
     * @covers \Gielfeldt\TransactionalPHP\Connection::rollbackTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::rollbackOperations
     */
    public function testRollbackTransaction(Connection $connection)
    {
        $operation = new Operation();
        $operation->onCommit(function () use (&$committed) {
            $committed = true;
        });
        $operation->onRollback(function () use (&$rolledback) {
            $rolledback = true;
        });

        $committed = false;
        $rolledback = false;
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->rollbackTransaction();

        $this->assertFalse($committed, 'Commit was performed.');
        $this->assertTrue($rolledback, 'Rollback was not performed.');

        $committed = false;
        $rolledback = false;
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->rollbackTransaction();
        $connection->commitTransaction();

        $this->assertFalse($committed, 'Commit was performed.');
        $this->assertTrue($rolledback, 'Rollback was not performed.');

        $committed = false;
        $rolledback = false;
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->commitTransaction();
        $connection->rollbackTransaction();

        $this->assertFalse($committed, 'Commit was performed.');
        $this->assertTrue($rolledback, 'Rollback was not performed.');
    }

    /**
     * Test rollback exception.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::rollbackTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::setDepth
     *
     * @expectedException \RuntimeException
     */
    public function testRollbackException(Connection $connection)
    {
        $connection->startTransaction();
        $connection->rollbackTransaction();
        $connection->rollbackTransaction();
    }

    /**
     * Test commit transaction.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::closeSavePoints
     * @covers \Gielfeldt\TransactionalPHP\Connection::collectOperations
     * @covers \Gielfeldt\TransactionalPHP\Connection::commitTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::commitOperations
     */
    public function testNestedTransaction(Connection $connection)
    {
        $accumulator = '';
        $operation = new Operation();
        $operation->onRollback(function ($operation, $connection) use (&$accumulator) {
            $accumulator .= 'rollback';
            if ($connection->getDepth() > 0) {
                $connection->addOperation($operation);
            }
        });

        $connection->startTransaction();
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->rollbackTransaction();
        $connection->rollbackTransaction();
        $connection->rollbackTransaction();

        $this->assertEquals('rollbackrollbackrollback', $accumulator, 'Nested rollback was not performed.');
    }

    /**
     * Test transaction depth.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::getDepth
     */
    public function testDepth(Connection $connection)
    {
        $this->assertEquals(0, $connection->getDepth(), 'Depth was not correct');

        $connection->startTransaction();
        $this->assertEquals(1, $connection->getDepth(), 'Depth was not correct');

        $connection->startTransaction();
        $this->assertEquals(2, $connection->getDepth(), 'Depth was not correct');

        $connection->rollbackTransaction();
        $this->assertEquals(1, $connection->getDepth(), 'Depth was not correct');

        $connection->commitTransaction();
        $this->assertEquals(0, $connection->getDepth(), 'Depth was not correct');
    }
}
