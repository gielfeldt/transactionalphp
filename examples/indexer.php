<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Indexer;
use Gielfeldt\TransactionalPHP\Operation;

$connection = new Connection();
$indexer = new Indexer($connection);

$operation = (new Operation())
    ->onCommit(function () {
        print "THIS WILL BE PRINTED IMMEDIATELY, BECAUSE NO TRANSACTION HAS BEGUN\n";
    })
    ->onRollback(function () {
        print "THIS WILL NEVER BE PRINTED, BECAUSE NO TRANSACTION HAS BEGUN\n";
    })
    ->onBuffer(function ($operation) use ($indexer) {
        print "INDEXING test1\n";
        $indexer->index('test1', $operation);
    })
    ->setValue('test1');
$connection->addOperation($operation);

// Start outer transaction.
$connection->startTransaction();

$operation = (new Operation())
    ->onCommit(function () {
        print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
    })
    ->onRollback(function () {
        print "THIS WILL NEVER BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
    })
    ->onBuffer(function ($operation) use ($indexer) {
        print "INDEXING test2\n";
        $indexer->index('test2', $operation);
    })
    ->setValue('test2');
$connection->addOperation($operation);

// Start inner transaction.
$connection->startTransaction();

$operation = (new Operation())
    ->onCommit(function () {
        print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
    })
    ->onRollback(function () {
        print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
    })
    ->onBuffer(function ($operation) use ($indexer) {
        print "INDEXING test3\n";
        $indexer->index('test3', $operation);
    })
    ->setValue('test3');
$connection->addOperation($operation);

$operation = (new Operation())
    ->onCommit(function () {
        print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK - second\n";
    })
    ->onRollback(function () {
        print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK - second\n";
    })
    ->onBuffer(function ($operation) use ($indexer) {
        print "INDEXING test3 - second\n";
        $indexer->index('test3', $operation);
    })
    ->setValue('test3 - second');
$connection->addOperation($operation);

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test3') as $operation) {
    print "Looked up test3 - found: " . $operation->getValue(). "\n";
}

// Rollback inner transaction.
$connection->rollbackTransaction();

// Commit inner transaction.
$connection->commitTransaction();
