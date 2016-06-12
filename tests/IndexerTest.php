<?php

namespace Gielfeldt\TransactionalPHP\Test;

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Operation;
use Gielfeldt\TransactionalPHP\Indexer;

/**
 * @covers \Gielfeldt\TransactionalPHP\Indexer
 */
class IndexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider.
     *
     * @return array
     *   Arguments for tests.
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
     */
    public function testSetup(Connection $connection)
    {
        $indexer = new Indexer($connection);

        $this->assertInstanceOf(
            '\\Gielfeldt\\TransactionalPHP\\Indexer',
            $indexer,
            'Indexer was not constructed properly.'
        );

        $check = $indexer->getConnection();
        $this->assertSame($connection, $check, 'Correct connection not set on indexer.');
    }

    /**
     * Test index.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     */
    public function testIndex(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $indexer->index($connection->addValue('value1'), 'test1');

        $check = $indexer->lookup('test1');
        $this->assertSame('value1', reset($check)->getValue(), 'Operations not found during lookup.');

        $operation = new Operation();
        $operation->onCommit(function () {
            return 'testresult';
        });
        $connection->addOperation($operation);
        $indexer->index($operation, 'test2');

        $check = $indexer->lookup('test2');
        $this->assertSame([$operation->idx($connection) => $operation], $check, 'Operations not found during lookup.');
    }

    /**
     * Test de-index.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     */
    public function testDeIndex(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $operation = $indexer->index($connection->addValue('value'), 'test');
        $indexer->deIndex($operation, 'test');

        $check = $indexer->lookup('test');
        $this->assertSame([], $check, 'Operations found during lookup.');
    }

    /**
     * Test automatic de-index.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     */
    public function testAutoDeIndex(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $operation1 = $indexer->index($connection->addValue('value1'), 'test1');

        $connection->startTransaction();
        $indexer->index($connection->addValue('value2'), 'test2');

        $connection->rollbackTransaction();

        $check = $indexer->lookup('test1');
        $this->assertSame(
            [$operation1->idx($connection) => $operation1],
            $check,
            'Operations not found during lookup.'
        );

        $check = $indexer->lookup('test2');
        $this->assertSame([], $check, 'Operations found during lookup.');

        $connection->commitTransaction();
        $check = $indexer->lookup('test1');
        $this->assertSame([], $check, 'Operations found during lookup.');
    }

    /**
     * Test lookup values.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     */
    public function testLookupValues(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $operation1 = $connection->addValue('value1');
        $operation2 = $connection->addValue('value2');
        $expected = [
            $operation1->idx($connection) => 'value1',
            $operation2->idx($connection) => 'value2',
        ];
        $indexer->index($operation1, 'test1');
        $indexer->index($operation2, 'test1');

        $check = $indexer->lookupValues('test1');
        $this->assertSame($expected, $check, 'Operations not found during lookup.');
    }

    /**
     * Test get operations
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     */
    public function testGetOperations(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $operation1 = $connection->addValue('value1');
        $operation2 = $connection->addValue('value2');
        $operation3 = $connection->addValue('value3');

        $indexer->index($operation1, 'test1');
        $indexer->index($operation2, 'test1');
        $indexer->index($operation3, 'test3');

        $expected = [
            $operation1->idx($connection) => $operation1,
            $operation2->idx($connection) => $operation2,
            $operation3->idx($connection) => $operation3,
        ];
        $check = $indexer->getOperations();
        $this->assertSame($expected, $check, 'Correct operations not found in indexer.');

        $expected = [
            'test1' => [
                $operation1->idx($connection) => $operation1,
                $operation2->idx($connection) => $operation2,
            ],
            'test3' => [
                $operation3->idx($connection) => $operation3,
            ],
        ];
        $check = $indexer->getIndex();
        $this->assertSame($expected, $check, 'Correct operations not found in indexer.');
    }

}
