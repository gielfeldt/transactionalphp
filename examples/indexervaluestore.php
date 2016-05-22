<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Indexer;

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

// Easy values lookup.
var_dump($indexer->lookupValues('test1'));
var_dump($indexer->lookupValues('test2'));

// Commit inner transaction.
$connection->commitTransaction();
print "Committed outer transaction\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getValue(). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getValue(). "\n";
}
