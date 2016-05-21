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
     * @covers \Gielfeldt\TransactionalPHP\Connection::getOperation
     * @covers \Gielfeldt\TransactionalPHP\Connection::startTransaction
     */
    public function testAddOperation(Connection $connection)
    {
        $operation = new Operation();
        $operation->setCallback(function () {
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
     * Test call.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::call
     */
    public function testCall(Connection $connection)
    {
        $callback = function () {
            return 'testresult';
        };
        $operation = $connection->call($callback);

        $this->assertFalse($connection->hasOperation($operation), 'Operation was not properly added.');

        $connection->startTransaction();
        $operation = $connection->call($callback);

        $this->assertTrue($connection->hasOperation($operation), 'Operation was not properly added.');
        $this->assertSame('testresult', $operation->execute(), 'Operation was not properly added.');
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
        $operation->setCallback(function () {
            return 'testresult';
        });

        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->removeOperation($operation);

        $check = $connection->hasOperation($operation);
        $this->assertFalse($check, 'Operation was not properly removed.');
    }

    /**
     * Test perform operation.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::performOperation
     */
    public function testPerformOperation(Connection $connection)
    {
        $performed = false;
        $operation = new Operation();
        $operation->setCallback(function () use (&$performed) {
            $performed = true;
        });

        $connection->startTransaction();
        $idx = $connection->addOperation($operation);
        $connection->performOperation($idx);
        $this->assertTrue($performed, 'Operation was not properly performed.');
    }

    /**
     * Test commit transaction.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Connection::commitTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::closeSavepoints
     */
    public function testCommitTransaction(Connection $connection)
    {
        $performed = false;

        $operation = new Operation();
        $operation->setCallback(function () use (&$performed) {
            $performed = true;
        });

        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->commitTransaction();

        $this->assertTrue($performed, 'Operation was not performed.');

        $performed = false;
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->commitTransaction();
        $connection->commitTransaction();

        $this->assertTrue($performed, 'Operation was not performed.');
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
     * @covers \Gielfeldt\TransactionalPHP\Connection::rollbackTransaction
     * @covers \Gielfeldt\TransactionalPHP\Connection::closeSavepoints
     */
    public function testRollbackTransaction(Connection $connection)
    {
        $performed = false;

        $operation = new Operation();
        $operation->setCallback(function () use (&$performed) {
            $performed = true;
        });

        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->rollbackTransaction();

        $this->assertFalse($performed, 'Operation was performed.');

        $performed = false;
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->rollbackTransaction();
        $connection->commitTransaction();

        $this->assertFalse($performed, 'Operation was performed.');

        $performed = false;
        $connection->startTransaction();
        $connection->startTransaction();
        $connection->addOperation($operation);
        $connection->commitTransaction();
        $connection->rollbackTransaction();

        $this->assertFalse($performed, 'Operation was performed.');
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
     *
     * @expectedException \RuntimeException
     */
    public function testRollbackException(Connection $connection)
    {
        $connection->startTransaction();
        $connection->rollbackTransaction();
        $connection->rollbackTransaction();
    }
}
