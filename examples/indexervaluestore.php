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

$indexer->index($connection->addMetadata('value', 'value1'), 'test1');
$indexer->index($connection->addMetadata('value', 'value2'), 'test1');
$indexer->index($connection->addMetadata('value', 'value1'), 'test2');
$indexer->index($connection->addMetadata('value', 'value2'), 'test2');
print "Added data to indexer\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getMetadata('value'). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getMetadata('value'). "\n";
}

// Start inner transaction.
$connection->startTransaction();
print "Started inner transaction\n";

$indexer->index($connection->addMetadata('value', 'value3'), 'test1');
$indexer->index($connection->addMetadata('value', 'value3'), 'test2');
print "Added data to indexer\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getMetadata('value'). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getMetadata('value'). "\n";
}

// Rollback inner transaction.
$connection->rollbackTransaction();
print "Rolled back inner transaction\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getMetadata('value'). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getMetadata('value'). "\n";
}

// Easy values lookup.
var_dump($indexer->lookupMetadata('test1', 'value'));
var_dump($indexer->lookupMetadata('test2', 'value'));

// Commit inner transaction.
$connection->commitTransaction();
print "Committed outer transaction\n";

foreach ($indexer->lookup('test1') as $operation) {
    print "Looked up test1 - found: " . $operation->getMetadata('value'). "\n";
}
foreach ($indexer->lookup('test2') as $operation) {
    print "Looked up test2 - found: " . $operation->getMetadata('value'). "\n";
}
