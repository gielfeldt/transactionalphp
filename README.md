# Transactional PHP

[![Build Status](https://scrutinizer-ci.com/g/gielfeldt/transactionalphp/badges/build.png?b=master)][8]
[![Test Coverage](https://codeclimate.com/github/gielfeldt/transactionalphp/badges/coverage.svg)][3]
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gielfeldt/transactionalphp/badges/quality-score.png?b=master)][7]
[![Code Climate](https://codeclimate.com/github/gielfeldt/transactionalphp/badges/gpa.svg)][5]

[![Latest Stable Version](https://poser.pugx.org/gielfeldt/transactionalphp/v/stable.svg)][1]
[![Latest Unstable Version](https://poser.pugx.org/gielfeldt/transactionalphp/v/unstable.svg)][1]
[![License](https://poser.pugx.org/gielfeldt/transactionalphp/license.svg)][4]
[![Total Downloads](https://poser.pugx.org/gielfeldt/transactionalphp/downloads.svg)][1]

## Installation

To install the Transactional PHP library in your project using Composer, first add the following to your `composer.json`
config file.
```javascript
{
    "require": {
        "gielfeldt/transactionalphp": "^0.4"
    }
}
```

Then run Composer's install or update commands to complete installation. Please visit the [Composer homepage][6] for
more information about how to use Composer.

### Transactional PHP

This class allows to buffer php code in a simulated transaction, thereby postponing the execution of the code until a
commit occurs.

#### Motivation

1. Problem of keeping external cache in sync with the database, see the Drupal module Cache Consistent.

#### Using the Transactional PHP library

##### Example 1 - Simple

```php
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
```

##### Example 2 - Commit and rollback

```php
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
```

##### Example 3 - Using the indexer

```php
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
```

##### Example 4 - Value store using the indexer for lookups

```php
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
```

For more examples see the examples/ folder.

#### Features

* Transactionalize PHP code.
* Index operations for lookup.

#### Caveats

1. Lots probably.


[1]:  https://packagist.org/packages/gielfeldt/transactionalphp
[2]:  https://circleci.com/gh/gielfeldt/transactionalphp
[3]:  https://codeclimate.com/github/gielfeldt/transactionalphp/coverage
[4]:  https://github.com/gielfeldt/transactionalphp/blob/master/LICENSE.md
[5]:  https://codeclimate.com/github/gielfeldt/transactionalphp
[6]:  http://getcomposer.org
[7]:  https://scrutinizer-ci.com/g/gielfeldt/transactionalphp/?branch=master
[8]:  https://scrutinizer-ci.com/g/gielfeldt/transactionalphp/build-status/master
