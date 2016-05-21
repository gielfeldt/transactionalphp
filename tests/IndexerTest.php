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
        $indexer->index('dummy', $connection->onCommit(function () {
        }));

        $operation = new Operation();
        $operation->onCommit(function () {
            return 'testresult';
        });
        $connection->addOperation($operation);
        $indexer->index('test1', $operation);
        $check = $indexer->lookup('test1');

        $this->assertSame([$operation->idx($connection) => $operation], $check, 'Operations not found during lookup.');
    }
}
