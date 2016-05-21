<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;

$connection = new Connection();

$connection->onCommit(function() {
    print "THIS WILL BE PRINTED IMMEDIATELY, BECAUSE NO TRANSACTION HAS BEGUN\n";
})
->onRollback(function() {
    print "THIS WILL NEVER BE PRINTED, BECAUSE NO TRANSACTION HAS BEGUN\n";
});

// Start outer transaction.
$connection->startTransaction();

$connection->onCommit(function() {
    print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
})
->onRollback(function() {
    print "THIS WILL NEVER BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
});

// Start inner transaction.
$connection->startTransaction();

$connection->onCommit(function() {
    print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
})
->onRollback(function() {
    print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
});

// Rollback inner transaction.
$connection->rollbackTransaction();

// Commit inner transaction.
$connection->commitTransaction();
