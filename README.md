# chippyash/Simple-Accounts

## Notice

This project has been stopped.  There is not enough traction on it to warrant 
its continuance.  If you want to take it over, please contact me. Or simply
fork it and carry on.

## Quality Assurance

![PHP 5.5](https://img.shields.io/badge/PHP-5.5-blue.svg)
![PHP 5.6](https://img.shields.io/badge/PHP-5.6-blue.svg)
![PHP 7](https://img.shields.io/badge/PHP-7-blue.svg)
[![Build Status](https://travis-ci.org/chippyash/Simple-Accounts.svg?branch=master)](https://travis-ci.org/chippyash/Simple-Accounts)
[![Test Coverage](https://codeclimate.com/github/chippyash/Simple-Accounts/badges/coverage.svg)](https://codeclimate.com/github/chippyash/Simple-Accounts/coverage)
[![Code Climate](https://codeclimate.com/github/chippyash/Simple-Accounts/badges/gpa.svg)](https://codeclimate.com/github/chippyash/Simple-Accounts)

The above badges and this documentation represent the current development branch.  
As a rule, I don't push to GitHub unless tests, coverage and usability are acceptable.  
This may not be true for short periods of time; on holiday, need code for some other 
downstream project etc.  If you need stable code, use a tagged version.  For definitive
documentation for a tgged version, read the README file in that version.
 
See the [Test Contract](https://github.com/chippyash/Simple-Accounts/blob/master/docs/Test-Contract.md)

### End of life notice

In March 2018, developer support will be withdrawn from this library for PHP <5.6. Older
versions of PHP are now in such little use that the added effort of maintaining 
compatibility is not effort effective.  See [PHP Version Stats](https://seld.be/notes/php-versions-stats-2017-1-edition)
 for the numbers.

## What?

Provides a simple double entry accounting system, that can be used as a component in
 a larger application.

### Features

*  Chart of Accounts \(see [here ](http://www.itzbits.co.uk/business-articles/67/What-is-a-Chart-of-Accounts.html) 
for a reasonable explanation\)
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
*  Extensible Journal system, allowing recording of account transactions

## Why?

Whilst full blown accounting systems are available, requiring a massive integration 
effort, some applications simply need to be able keep some form of internal account. 
This library is the direct descendant of something I wrote for a [client](http://www.amaranthgames.com/) 
many years ago to keep account of game points earned on a web site.  Using the double 
entry accounting paradigm allowed the site owner to keep track of who had gathered 
points, and in which ways, whilst at the same time seeing what this meant to their 
business as game points translated into real world value for the customer by way of 
discounts and prizes.
   
## When

The current library support Organisations, Charts of Account, Journals and Control Accounts.  

### Roadmap

- Accounting
    - closing accounts
- Reporting
    - balance sheet
    - trial balance
    - profit and loss
    
If you want more, either suggest it, or better still, fork it and provide a pull request.

Check out [ZF4 Packages](http://zf4.biz/packages?utm_source=github&utm_medium=web&utm_campaign=blinks&utm_content=accounts) for more packages

## How

### Coding Basics

#### Creating a new chart of accounts

##### Creating it manually
<pre>
use SAccounts\Chart;
use SAccounts\Organisation;
use Chippyash\Currency\Factory as Currency;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;

$org = new Organisation(new IntType(1), new StringType('Foo'), Currency::create('gbp'));
$chart = new Chart(new StringType('Foo Chart'), $org);
</pre>

##### Using the Accountant
The Accountant is a useful 'person'\! But as usual they come at a cost: they need a 
fileClerk to do some of the work for them, and you have to give them that as payment.  
A fileClerk implements the AccountStorageInterface. A simple example that allows 
saving of Charts as serialized PHP file is provided to get you started, but of course
you can create your own.

<pre>
use SAccounts\Accountant;
use SAccounts\Storage\Account\Serialized;

$fileClerk = new Serialized(new StringType('/path/To/My/Account/Store'));
$accountant = new Accountant($fileClerk);
</pre>

To create a chart via the accountant you still need to tell it what organisation the 
new chart is for, and also which COA template you want to use. A simple 'personal 
accounts' template is provided, which is an XML file. You can create and supply your own.

<pre>
use SAccounts\ChartDefinition;
use SAccounts\Organisation;
use Chippyash\Type\String\StringType;
use Chippyash\Type\Number\IntType;
use Chippyash\Currency\Factory as Currency;

$def = new ChartDefinition(new StringType('/path/to/definitions/personal.xml'));
$org = new Organisation(new IntType(1), new StringType('Foo'), Currency::create('gbp'));
$chart = $accountant->createChart(new StringType('Name of Chart'), $org, $def);
</pre>

#### Adding accounts to the chart
Once you have a chart you may want  to add new accounts to it.  If you have created 
one manually from scratch it will not have a root account, so you need to add that 
first.  All accounts are identified by a 'nominal code'.  This is of type 
Nominal (based on chippyash\Type\String\DigitType) and is a numeric string.  You can 
use any nominal code structure you like, but make sure you give yourself enough room 
to add the accounts you want.  Take a look at the definitions/personal.xml for some insight.

Accounts also need a type for the account.  These are defined in the AccountType 
enum class and are simply created by calling the class constant as a method. See the 
link in the thanks section for more information about Enums.

- add a root account

<pre>
use SAccounts\Nominal;
use SAccounts\AccountType;

ac1 = new Account($chart, new Nominal('2000'), AccountType::ASSET(), new StringType('Asset'));
$chart->addAccount($ac)
</pre>

- add a child account

<pre>
ac2 = new Account($chart, new Nominal('2100'), AccountType::BANK(), new StringType('Bank'));
$chart->addAccount($ac, $ac1->id())
</pre>

#### Saving the chart
<pre>
$accountant->fileChart($chart));
</pre>

#### Fetching the chart
In fetching a chart you need to know the id of the organisation that it belongs to

<pre>
$chart = $accountant->fetchChart(new StringType('Name of Chart'), new IntType(1));
</pre>

This allows you to create multiple COAs for an organisation, potentially allowing
you to keep task specific COAs or COAs for different currencies.

#### Making entries into accounts

You can make debit and credit entries to any account. Obviously, to maintain double 
entry accounting rules, you'll generally make one of each for any transaction.

You don't need to keep track of accounts, simply get them from the chart using their id.

Whilst it is not enforced, you are advised to use the same currency that you used for 
your organisation when creating amounts to debit and credit.

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

#### Using Journals

Whilst an Account records the value state at any given point in time, and a Chart holds 
the state of a collection (tree) of accounts, a Journal is responsible for recording 
the transaction history that led to the current state of the Account.
 
You may use the library without using Journalling at all, but most systems will want 
a transaction history. The Accountant can make use of an optional 'Journalist' that 
implements the JournalStorageInterface to create, save and amend both a Journal and 
the transactions that it records.

You must first supply a Journalist in the form of a JournalStorageInterface.  An 
example is provided, Accounts\Storage\Journal\Xml which stores the Journal and its 
transactions into an XML file.  You can provide your own to store against any other
storage mechanism that you want to use.
 
<pre>
use SAccounts\Storage\Journal\Xml as Journalist;

$accountant->setJournalist(new Journalist(new StringType('/path/to/my/journal/store/folder')));
</pre>
 
#### Creating a Journal

<pre>
use Chippyash\Currency\Factory as Currency;

$journal = $accountant->createJournal(new StringType('My Journal'), Currency::create('gbp'));
</pre>

Under most circumstances, you'll associate an Organisation, and a Chart with a Journal, 
so it makes sense to use the same Currency:

<pre>
$journal = $accountant->createJournal(new StringType('My Journal'), $chart->getOrg()->getCurrency());
</pre>

#### Fetching a Journal that you already made

<pre>
use SAccounts\Storage\Journal\Xml as Journalist;

$accountant->setJournalist(new Journalist(new StringType('/path/to/my/journal/store/folder')));
$journal = $accountant->fetchJournal();
</pre>

You can also store a journal via the accountant if you amend its definition

#### Creating transactions in the journal

You can either manage the link between the Journal and the Chart yourself by calling 
their appropriate store mechanisms (see the code, tests and diagrams for that,) or 
more simply, ask the accountant to do it for you.  In either case, you first of all
need a Transaction. Transactions are provided by way of the `SAccounts\Transaction\SplitTransaction`
and `SAccounts\Transaction\SimpleTransaction`.  `SimpleTransaction` is provided as helper
for creating and writing out transactions that consist of a pair of balanced debit and credit
amounts.

<pre>
use SAccounts\Transaction\SimpleTransaction;
use SAccounts\Nominal;

$drAc = new Nominal('0000');
$crAc = new Nominal('1000');
$amount = Currency::create($chart->getOrg()->getCurrencyCode(), 12.26);
$txn = new  SimpleTransaction($drAc, $crAc, $amount);
</pre>

You can set an optional 4th parameter when creating a SimpleTransaction:
 
<pre>
$txn = new  SimpleTransaction($drAc, $crAc, $amount, new StringType('This is a note'));
</pre>

By default the date and time for the transaction is set to now().  You can set an 
optional 5th parameter when creating a SimpleTransaction and supply a DateTime object
of your own choosing.

<pre>
$txn = new  SimpleTransaction($drAc, $crAc, $amount, new StringType(''), new \DateTime('2015-12-03T12:14:30Z));
</pre>

To record a transaction and update the chart of accounts you can now use the Accountant again:

<pre>
$txn = $accountant->writeTransaction($txn, $chart, $journal);
//or
$accountant->writeTransaction($txn, $chart, $journal);
</pre>

The Transaction will now have its transaction id set, which you can recover via:

<pre>
$txnId = $txn->getId() //returns IntType
</pre>

You don't need to save the Journal, as it is inherently transactional, but don't forget 
to save your Chart once you have finished writing transactions. 

The full power of the transaction is provided by the `SplitTransaction`. And remember,
 that when you read transactions back from the journal they will be in SplitTransaction
 format.  A split transaction allows you to have, say, one debit entry and three credit
 entries.  As long as the total debit entry amounts equal the total credit entry 
 amounts, you have a balanced transaction, i.e. a valid double entry transaction.
 
With power comes a little more complexity, as you'd expect! 

<pre>
use SAccounts\Transaction\SimpleTransaction;
use SAccounts\Transaction\Entry;
use SAccounts\Nominal;
use Chippyash\Type\String\StringType;
use Chippyash\Currency\Factory as Currency;

$txn = new SplitTransaction() // date == now(), note == ''
$txn = new SplitTransaction(new DateTime());
$txn = new SplitTransaction(null, new StringType('foo'));
$txn = new SplitTransaction(new DateTime(), new StringType('foo'));

//the following is analogous to a SimpleTransaction
$note = new StringType('foo bar');
$dt = new \DateTime();
$amount = Currency::create('gbp', 12.26);
$txn = (new SplitTransaction($dt, $note))
    ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
    ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
    
</pre>

When creating an Entry, you need to tell it:
- which account to use
- how much 
- whether to debit or credit

To create true split transaction, lets use the following example:
- bank account: 3001
- vat account: 6007
- items account: 9056
- total transaction £120.00, £100 for the item, £20 as VAT

<pre>
$txn = (new SplitTransaction($dt, $note))
    ->addEntry(new Entry(new Nominal('3001'), Currency::create('gbp', 120), AccountType::DR()))
    ->addEntry(new Entry(new Nominal('6007'), Currency::create('gbp', 20), AccountType::CR()))
    ->addEntry(new Entry(new Nominal('9056'), Currency::create('gbp', 100), AccountType::CR()));
</pre>

On the whole it is a really bad idea to create an unbalanced transaction and you can
check this with `checkBalance()` which returns true if the transaction is balanced else false.

You can also do a simple check to see if the transaction conforms to being simple
using the `isSimple()` method:

<pre>
$drAc = $txn->getDrAc();
if ($txn->isSimple()) {
    $actualDrAc = $drAc[0];
} else {
    //you have an array of debit accounts, so process them
}
</pre>

`getCrAc()` gets the credit accounts on a transaction.

The `getEntries()` method of a SplitTransaction returns a SAccounts\Transaction\Entries
collection of entries.

#### Control Accounts

In the sense of an [accounting definition](https://en.wikipedia.org/wiki/Controlling_account) of Control
Accounts, the Simple Accounts `Control Account` can certainly be used to point to an adjustment account
in your Chart. However, in practical use, Control Accounts have a more broadly defined 
use; that of pointing to specific accounts within the Chart.  So in this sense, the
Simple Accounts Control Account is simply a pointer to another account.

In programming terms, we set up a Collection (`Control\Links`) of Control Accounts (`Control\Link`). 
Let's use an example:

You have a system in which you want generic cash transactions to go to specific
accounts:

- cash to/from the 'bank' account
- purchases to the 'sundries' account
- sales to the 'cash sales' account

The problem is, that as your COA (or business) grows, the actual account in the COA
that you want to use may change.  By dereferencing the actual account with a Control
Account, your main code can remain the same, yet allowing you to reconfigure at will.

<pre>
use SAccounts\Control;

$linkArray = [
    new Control\Link(new StringType('bank'), new Nominal('1000')),
    new Control\Link(new StringType('sundries'), new Nominal('2000')),
    new Control\Link(new StringType('cash sales'), new Nominal('3000')),
];
$ctrlAcs = (new Control\Links($linkArray));

$txn = (new SplitTransaction($dt, $note))
    ->addEntry($ctrlAcs->getLinkId(new StringType('bank')), Currency::create('gbp', 120), AccountType::DR()))
    ->addEntry($ctrlAcs->getLinkId(new StringType('cash sales')), Currency::create('gbp', 120), AccountType::CR()));

$txn2 = (new SplitTransaction($dt, $note))
    ->addEntry($ctrlAcs->getLinkId(new StringType('bank')), Currency::create('gbp', 90), AccountType::CR()))
    ->addEntry($ctrlAcs->getLinkId(new StringType('sundries')), Currency::create('gbp', 90), AccountType::DR()));

</pre>

It is really as simple as that.  I've not included a storage mechanism for Control Accounts
on the basis, that it is likely that you'll dependency inject them into your application,
 however there is an XML XSD in the definitions folder, with an example XML file in the 
 docs directory.  In practice you may find yourself using a number of Control Account
 Collections in an application.
 
### Class diagrams

![UML Diagram](https://github.com/chippyash/Simple-Accounts/blob/master/docs/ClassesForAccounts.png)
![UML Diagram](https://github.com/chippyash/Simple-Accounts/blob/master/docs/ClassesForJournals.png)
![UML Diagram](https://github.com/chippyash/Simple-Accounts/blob/master/docs/ClassesForControlAccounts.png)

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
    "chippyash/simple-accounts": "~2"
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

Back in the day, when the first Simple Accounts was written, I had to write a lot of 
support code myself.  In this version I have been able to take advantage of the work 
of others. As well as the normal suspects of [PHPUnit](https://github.com/sebastianbergmann/phpunit)
 and [vfsStream](https://github.com/mikey179/vfsStream) for writing the test code,
I'd like to highlight some others:

* [PHP Enum](https://github.com/myclabs/php-enum) : a neat implementation of enums for PHP
* [Tree](https://github.com/nicmart/Tree) : A simple tree component that supports the 
visitor pattern allowing for easy extension

## License

This software library is released under the [GNU GPL V3 or later license](http://www.gnu.org/copyleft/gpl.html)

This software library is Copyright (c) 2015-2018, Ashley Kitson, UK

A commercial license is available for this software library, please contact the author. 
It is normally free to deserving causes, but gets you around the limitation of the GPL
license, which does not allow unrestricted inclusion of this code in commercial works.

## History

V1.0.0 Original release

V1.1.0 Journals added

V1.2.0

- replaced chippyash\Accounts namespace with SAccounts
- Transaction deprecated, use SimpleTransaction (Transaction proxies to SimpleTransaction and will be removed in the future)
- SplitTransactions introduced, use these for preference
- BC break with XML Journal file format to accommodate split transactions 

V1.3.0 Added Control Accounts

V1.4.0 Update dependencies

V1.4.1 Add link to packages

V1.4.2 Verify PHP 7 compatibility

V1.4.3 Code cleanup

V1.4.4 Dependency update

V1.4.5 PhpUnit test suite update

V1.4.6 Update build script

V2.0.0 BC break in some interface definitions to support implementation of DB based systems.

V2.1.0 Add ability to set chart root node