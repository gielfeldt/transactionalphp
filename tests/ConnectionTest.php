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
     * @covers \Gielfeldt\TransactionalPHP\Connection::id
     */
    public function testSetup(Connection $connection)
    {
        $id = $connection->id();
        $this->assertEquals('testid', $id, 'ID was not properly set.');

        $connection = new Connection('testid2');
        $id = $connection->id();
        $this->assertEquals('testid2', $id, 'ID was not properly set.');
    }

    /**
     * Test setup.
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
        $check = $connection->addOperation($operation);

        $this->assertSame('testresult', $check, 'Operation was not properly added.');

        $connection->startTransaction();
        $idx = $connection->addOperation($operation);
        $check = $connection->getOperation($idx);

        $this->assertSame($operation, $check, 'Operation was not properly added.');
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
        $idx = $connection->addOperation($operation);
        $connection->removeOperation($idx);

        $check = $connection->getOperation($idx);
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
        $connection->performOperation($idx, true);
        $check = $connection->getOperation($idx);
        $this->assertFalse($check, 'Operation was not properly removed.');

        $idx = $connection->addOperation($operation);
        $connection->performOperation($idx, false);
        $check = $connection->getOperation($idx);
        $this->assertSame($operation, $check, 'Operation was not preserved properly.');
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
