<?php

namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Operation;

$connection = new Connection();

// Start outer transaction.
$connection->startTransaction();

$connection->addOperation((new Operation())
    ->setCallback(function() {
        // Here I will do my stuff.
        print "THIS WILL BE PRINTED, BECAUSE THIS WILL BE COMMITTED\n";
    }));

// Start inner transaction.
$connection->startTransaction();

$connection->addOperation((new Operation())
    ->setCallback(function() {
        // Here I will do my stuff.
        print "THIS WILL NOT BE PRINTED, BECAUSE THIS WILL BE ROLLED BACK\n";
    }));

// Rollback inner transaction.
$connection->rollbackTransaction();

// Commit inner transaction.
$connection->commitTransaction();
