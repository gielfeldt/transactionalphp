<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Indexer;
use Gielfeldt\TransactionalPHP\Operation;

$connection = new Connection();
$indexer = new Indexer($connection);

// Start outer transaction.
$connection->startTransaction();
print "Started outer transaction\n";

$indexer->index('test1', $connection->addValue('value1'));
$indexer->index('test1', $connection->addValue('value2'));
$indexer->index('test2', $connection->addValue('value1'));
$indexer->index('test2', $connection->addValue('value2'));
print "Added data to indexer\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getValue(). "\n";
}

// Start inner transaction.
$connection->startTransaction();
print "Started inner transaction\n";

$indexer->index('test1', $connection->addValue('value3'));
$indexer->index('test2', $connection->addValue('value3'));
print "Added data to indexer\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getValue(). "\n";
}

// Rollback inner transaction.
$connection->rollbackTransaction();
print "Rolled back inner transaction\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getValue(). "\n";
}

// Commit inner transaction.
$connection->commitTransaction();
print "Committed outer transaction\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getValue(). "\n";
}
