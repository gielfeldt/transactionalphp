<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Operation;

$connection = new Connection();

$operation = new Operation();
$operation->onCommit(function () {
    print "THIS WILL BE PRINTED IMMEDIATELY, BECAUSE NO TRANSACTION HAS BEGUN\n";
})
->onRollback(function () {
    print "THIS WILL NEVER BE PRINTED, BECAUSE NO TRANSACTION HAS BEGUN\n";
});
$connection->addOperation($operation);

// Start outer transaction.
$connection->startTransaction();

$operation = new Operation();
$operation->onCommit(function () {
    print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
})
->onRollback(function () {
    print "THIS WILL NEVER BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
});
$connection->addOperation($operation);

// Start inner transaction.
$connection->startTransaction();

$operation = new Operation();
$operation->onCommit(function () {
    print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
})
->onRollback(function () {
    print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
});
$connection->addOperation($operation);

// Rollback inner transaction.
$connection->rollbackTransaction();

// Commit inner transaction.
$connection->commitTransaction();
