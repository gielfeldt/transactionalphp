<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;

$connection = new Connection();

// Start outer transaction.
$connection->startTransaction();

$connection->onCommit(function () {
    print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
});

// Start inner transaction.
$connection->startTransaction();

$connection->onCommit(function () {
    print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
});

// Rollback inner transaction.
$connection->rollbackTransaction();

// Commit inner transaction.
$connection->commitTransaction();
