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
     *
     * @covers \Gielfeldt\TransactionalPHP\Indexer::__construct
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
     *
     * @covers \Gielfeldt\TransactionalPHP\Indexer::index
     * @covers \Gielfeldt\TransactionalPHP\Indexer::lookup
     */
    public function testIndex(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $indexer->index('value', $connection->addValue('testvalue'));

        $check = $indexer->lookup('value');
        $this->assertSame('testvalue', reset($check)->getValue(), 'Operations not found during lookup.');

        $operation = new Operation();
        $operation->onCommit(function () {
            return 'testresult';
        });
        $connection->addOperation($operation);
        $indexer->index('test1', $operation);

        $check = $indexer->lookup('test1');
        $this->assertSame([$operation->idx($connection) => $operation], $check, 'Operations not found during lookup.');
    }

    /**
     * Test de-index.
     *
     * @param Connection $connection
     *   The connection to perform tests on.
     *
     * @dataProvider connectionDataProvider
     *
     * @covers \Gielfeldt\TransactionalPHP\Indexer::deIndex
     * @covers \Gielfeldt\TransactionalPHP\Indexer::lookup
     */
    public function testDeIndex(Connection $connection)
    {
        $indexer = new Indexer($connection);
        $connection->startTransaction();
        $operation = $indexer->index('value', $connection->addValue('testvalue'));
        $indexer->deIndex('value', $operation);

        $check = $indexer->lookup('value');
        $this->assertSame([], $check, 'Operations found during lookup.');
    }
}
