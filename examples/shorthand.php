<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;

$connection = new Connection();

$connection->call(function() {
    print "THIS WILL BE PRINTED IMMEDIATELY, BECAUSE NO TRANSACTION HAS BEGUN\n";
});

// Start outer transaction.
$connection->startTransaction();

$connection->call(function() {
    // Here I will do my stuff.
    print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
});

// Start inner transaction.
$connection->startTransaction();

$connection->call(function() {
    // Here I will do my stuff.
    print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
});

// Rollback inner transaction.
$connection->rollbackTransaction();

// Commit inner transaction.
$connection->commitTransaction();
