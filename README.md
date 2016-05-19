# Transactional PHP

[![Build Status](https://scrutinizer-ci.com/g/gielfeldt/transactionalphp/badges/build.png?b=master)][8]
[![Test Coverage](https://codeclimate.com/github/gielfeldt/transactionalphp/badges/coverage.svg)][3]
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gielfeldt/transactionalphp/badges/quality-score.png?b=master)][7]
[![Code Climate](https://codeclimate.com/github/gielfeldt/transactionalphp/badges/gpa.svg)][5]

[![Latest Stable Version](https://poser.pugx.org/gielfeldt/transactionalphp/v/stable.svg)][1]
[![Latest Unstable Version](https://poser.pugx.org/gielfeldt/transactionalphp/v/unstable.svg)][1]
[![License](https://poser.pugx.org/gielfeldt/transactionalphp/license.svg)][4]
[![Total Downloads](https://poser.pugx.org/gielfeldt/transactionalphp/downloads.svg)][1]

[![Documentation Status](https://readthedocs.org/projects/transactionalphp/badge/?version=stable)][12]
[![Documentation Status](https://readthedocs.org/projects/transactionalphp/badge/?version=latest)][12]

## Installation

To install the Transactional PHP library in your project using Composer, first add the following to your `composer.json`
config file.
```javascript
{
    "require": {
        "gielfeldt/transactionalphp": "~1.0"
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

#### Example 1 - using the Transactional PHP library

```php
namespace Gielfeldt\TransactionalPHP\Example;

require 'vendor/autoload.php';

use Gielfeldt\TransactionalPHP\Connection;

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
[9]:  https://coveralls.io/github/gielfeldt/transactionalphp
[10]: https://travis-ci.org/gielfeldt/transactionalphp
[11]: https://www.versioneye.com/user/projects/55cb2eb9dfed0a001e000200
[12]: https://readthedocs.org/projects/transactionalphp/?badge=latest
