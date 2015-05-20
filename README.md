# chippyash/Simple-Accounts

## Quality Assurance

Certified for PHP 5.5

[![Build Status](https://travis-ci.org/chippyash/Simple-Accounts.svg?branch=master)](https://travis-ci.org/chippyash/Simple-Accounts)
[![Coverage Status](https://coveralls.io/repos/chippyash/Simple-Accounts/badge.png)](https://coveralls.io/r/chippyash/Simple-Accounts)

See the [Test Contract](https://github.com/chippyash/Simple-Accounts/blob/master/docs/Test-Contract.md)

## What?

Provides a simple double entry accounting system, that can be used as a component in a larger application.

### Features

*  Chart of Accounts \(see [here ](http://www.itzbits.co.uk/business-articles/67/What-is-a-Chart-of-Accounts.html) for a reasonable explanation\)
    * You can define your own chart structures
*  Account types
    * DR
        * ASSET
             * BANK
            * CUSTOMER
        * EXPENSE
    * CR
        * INCOME
        * LIABILITY
            * EQUITY
            * SUPPLIER
        
*  Ability to save and retrieve a Chart through a simple interface
*  Organisation concept that can have multiple Chart of Accounts
*  Fantasy currencies are catered for 

The library is released under the [GNU GPL V3 or later license](http://www.gnu.org/copyleft/gpl.html)

Commercial licenses are available

## Why?

Whilst full blown accounting systems are available, requiring a massive integration effort, some applications simply
need to be able keep some form of internal account. This library is the direct descendant of something I wrote for 
a [client](http://www.amaranthgames.com/) many years ago to keep account of game points earned on a web site.  Using the 
double entry accounting paradigm allowed the site owner to keep track of who had gathered points, and in which ways, 
whilst at the same time seeing what this meant to their business as game points translated into real world value for 
the customer by way of discounts and prizes.
   
## When

The current library supports a Chart of Accounts.  

### Roadmap

- Journals
    - recording transactions
    - simplify transaction entries
    - closing accounts at year end
    - control accounts
- Reporting
    - balance sheet
    - trial balance
    - profit and loss
    
If you want more, either suggest it, or better still, fork it and provide a pull request.

See [The Matrix Packages](http://the-matrix.github.io/packages/) for other packages from chippyash

## How

### Coding Basics

#### Creating a new chart of accounts

##### Creating it manually
<pre>
use chippyash\Accounts\Chart;
use chippyash\Accounts\Organisation;
use chippyash\Currency\Factory as Currency;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;

$org = new Organisation(new IntType(1), new StringType('Foo'), Currency::create('gbp'));
$chart = new Chart(new StringType('Foo Chart'), $org);
</pre>

##### Using the Accountant
The Accountant is a useful 'person'\! But as usual they come at a cost: they need a fileClerk to do some of the
work for them, and you have to give them that as payment.  A fileClerk implements the AccountStorageInterface. A simple
example that allows saving of Charts as serialized PHP file is provided to get you started, but of course you can create
your own.

<pre>
use chippyash\Accounts\Accountant;
use chippyash\Accounts\Storage\Account\Serialized;

$fileClerk = new Serialized(new StringType('/path/To/My/Account/Store'));
$accountant = new Accountant($fileClerk);
</pre>

To create a chart via the accountant you still need to tell it what organisation the new chart is for, and also which
COA template you want to use. A simple 'personal accounts' template is provided, which is an XML file. You can create
and supply your own.

<pre>
use chippyash\Accounts\ChartDefinition;
use chippyash\Accounts\Organisation;
use chippyash\Type\String\StringType;
use chippyash\Type\Number\IntType;
use chippyash\Currency\Factory as Currency;

$def = new ChartDefinition(new StringType('/path/to/definitions/personal.xml'));
$org = new Organisation(new IntType(1), new StringType('Foo'), Currency::create('gbp'));
$chart = $accountant->createChart(new StringType('Name of Chart'), $org, $def);
</pre>

#### Adding accounts to the chart
Once you have a chart you may want  to add new accounts to it.  If you have created one manually from scratch it will
not have a root account, so you need to add that first.  All accounts are identified by a 'nominal code' or id.  This
is of type Nominal (based on chippyash\Type\String\DigitType) and is a numeric string.  You can use any nominal code
structure you like, but make sure you give yourself enough room to add the accounts you want.  Take a look at the
definitions/personal.xml for some insight.

Accounts also need a type for the account.  These are defined in the AccountType enum class and are simply created
by calling the class constant as a method. See the link in the thanks section for more information about Enums.

- add a root account

<pre>
use use chippyash\Accounts\Nominal;
use chippyash\Accounts\AccountType;

ac1 = new Account($chart, new Nominal('2000'), AccountType::ASSET(), new StringType('Asset'));
$chart->addAccount($ac)
</pre>

- add a child account

<pre>
ac2 = new Account($chart, new Nominal('2100'), AccountType::BANK(), new StringType('Bank'));
$chart->addAccount($ac, $ac1->getId())
</pre>

#### Saving the chart
<pre>
$accountant->fileChart($chart));
</pre>

#### Fetching the chart
<pre>
$chart = $accountant->fetchChart(new StringType('Name of Chart'));
</pre>

#### Making entries into accounts

You can make debit and credit entries to any account. Obviously, to maintain double entry accounting rules, you'll
generally make one of each for any transaction.

You don't need to keep track of accounts, simply get them from the chart using their id.

Whilst it is not enforced, you are advised to use the same currency that you used for your organisation when creating
amounts to debit and credit.

<pre>
//can be used in most situations
$amount = Currency::create($chart->getOrg()->getCurrencyCode(), 12.26);

//use this method if locale issues are important or you are using a fantasy currency
$amount = clone $chart->getOrg()->getCurrency();
$amount->setAsFloat(12.26);
//or use set() if you know your currency precision
$amount->set(1226);

$chart->getAccount(new Nominal('1000'))->debit($amount);
$chart->getAccount(new Nominal('2000'))->credit($amount);
</pre>

#### Getting account values

All account values are expressed as [chippyash\Currency\Currency](https://github.com/chippyash/currency) objects.

- debit and credit amounts

<pre>
$debitAsInt = $chart->getAccount(new Nominal('1000'))->getDebit()->get();
$debitAsFloat = $chart->getAccount(new Nominal('1000'))->getDebit()->getAsFloat()
echo $chart->getAccount(new Nominal('1000'))->getDebit()->display();
$creditAsInt = $chart->getAccount(new Nominal('1000'))->getCredit()->get();
$creditAsFloat = $chart->getAccount(new Nominal('1000'))->getCredit()->getAsFloat()
echo $chart->getAccount(new Nominal('1000'))->getCredit()->display();
</pre>

- account balance

For all account types (excluding DUMMY and REAL) get the account balance:

<pre>
$balanceAsInt = $chart->getAccount(new Nominal('1000'))->getBalance()->get();
$balanceAsFloat = $chart->getAccount(new Nominal('1000'))->getBalance()->getAsFloat();
echo $chart->getAccount(new Nominal('1000'))->getBalance()->display();
</pre>

The balance respects the conventions of DR and CR accounts.:

- DR balance = dr-cr
- CR balance = cr-dr

### Class diagram

![UML Diagram](https://github.com/chippyash/Simple-Accounts/blob/master/docs/Classes.png)

### Changing the library

1.  fork it
2.  write the test
3.  amend it
4.  do a pull request

Found a bug you can't figure out?

1.  fork it
2.  write the test
3.  do a pull request

NB. Make sure you rebase to HEAD before your pull request

Or - raise an issue ticket.

## Where?

The library is hosted at [Github](https://github.com/chippyash/Simple-Accounts). It is
available at [Packagist.org](https://packagist.org/packages/chippyash/simple-accounts)

### Installation

Install [Composer](https://getcomposer.org/)

#### For production

<pre>
    "chippyash/simple-accounts": "~1.0.0"
</pre>

#### For development

Clone this repo, and then run Composer in local repo root to pull in dependencies

<pre>
    git clone git@github.com:chippyash/Simple-Accounts.git Accounts
    cd Accounts
    composer update
</pre>

To run the tests:

<pre>
    cd Accounts
    vendor/bin/phpunit -c test/phpunit.xml test/
</pre>

## Thanks

Back in the day, when the first Simple Accounts was written, I had to write a lot of support code myself.  In this version
I have been able to take advantage of the work of others. As well as the normal suspects of [PHPUnit](https://github.com/sebastianbergmann/phpunit)
 and [vfsStream](https://github.com/mikey179/vfsStream) for writing the test code,
I'd like to highlight some others:

* [PHP Enum](https://github.com/myclabs/php-enum) : a neat implementation of enums for PHP
* [Tree](https://github.com/nicmart/Tree) : A simple tree component that supports the visitor pattern allowing for easy extension

## History

V1.0.0 Original release

