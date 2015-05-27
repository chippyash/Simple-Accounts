# Chippyash Simple Accounts

## chippyash\Test\Accounts\Account

*  You can create any valid account type
*  You can debit and credit any account type
*  You can get a balance for account types that support it
*  Getting balance of a real account type will throw an exception
*  Getting balance of a dummy account type will throw an exception
*  Debiting an account will debit its parent if one exists in a chart
*  Crediting an account will credit its parent if one exists in a chart
*  You can get the account id
*  You can get the account type
*  You can get the account name

## chippyash\Test\Accounts\AccountType

*  Can get values as constants
*  Can get values as classes using static methods
*  Can get a debit column title for a valid account type
*  Get a debit column with invalid account type will throw exception
*  Get a credit column with invalid account type will throw exception
*  Can get a credit column title for a valid account type
*  Will get correct balance for all valid account types
*  Get a balance with invalid account type will throw exception

## chippyash\Test\Accounts\Accountant

*  An accountant can file a chart
*  An accountant will throw exception if it cannot file a chart
*  An accountant can fetch a chart
*  An accountant can create a new chart of accounts
*  You can set an optional journalist
*  You can create a journal if journalist is set
*  Creating a journal without a journalist will throw exception
*  You can file a journal to storage
*  Filing a journal to storage when journalist not set throws exception
*  Filing a journal to storage throws exception if journalist fails to write
*  You can fetch a journal from storage
*  Fetching a journal from storage with no journalist set will throw exception
*  You can write a transaction to a journal and update a chart

## chippyash\Accounts\ChartDefinition

*  Can construct with valid file name
*  Construction with invalid file name will throw exception
*  Construction with valid file name will return class
*  Getting the definition will throw exception if definition file is invalid xml
*  Getting definition will throw exception if definition fails validation
*  Getting the definition will return a dom document with valid definition file

## chippyash\Test\Accounts\Chart

*  Construction creates chart
*  You can give a chart an optional tree in construction
*  You can add an account if it is not already in the chart
*  Adding an account that already exists in chart will throw exception
*  You can add an account with a parent
*  You can get an account if it exists
*  Trying to get a non existent account will throw an exception
*  Deleting a non existent account will throw an exception
*  You cannot delete an account if its balance is non zero
*  You can delete an account if its balance is zero
*  You can test if a chart has an account
*  Trying to get a parent id of a non existent account will throw an exception
*  Getting the parent id of an account that has a parent will return the parent id

## chippyash\Test\Accounts\Journal

*  Writing a transaction will return transaction with id set
*  Reading a transaction will return a transaction
*  Reading transactions for an account will return an array of transactions
*  You can get name of journal

## chippyash\Test\Accounts\Organisation

*  You can get organisation id
*  You can get organisation name
*  You can get organisation currency
*  You can get organisation currency code

## chippyash\Test\Accounts\Storage\Account\Serialized

*  Construction with non existent directory throws an exception
*  You can send a chart
*  Fetching a non existent chart will throw an exception
*  You can fetch a chart

## chippyash\Test\Accounts\Storage\Journal\Xml

*  Construction takes an optional journal name
*  Successful journal write will return true
*  Successful journal write will store journal definition in x m l file
*  You can create a new journal definition file
*  You can amend an existing journal definition file
*  Not setting journal name before a read will throw an exception
*  Reading a journal definition will throw exception if file does not exist
*  Reading a journal definition will throw exception if file is not a journal definition
*  Reading a journal definition will return a journal
*  Not setting journal name before a transaction write will throw an exception
*  Writing a transaction will save it to x m l file and increment the transaction sequence number
*  Not setting journal name before a transaction read will throw an exception
*  Reading a transaction that exists will return a transaction object
*  Reading a transaction that does not exist will return null
*  Reading transactions for an account that does not exist will return empty array
*  Reading transactions for an account that does exist will return an array of transactions

## chippyash\Test\Accounts\Transaction

*  Basic construction sets an empty note on the transaction
*  Basic construction sets date for today on the transaction
*  You can set an optional note on construction
*  You can set an optional date on construction
*  Constructing a transaction does not set its id
*  You can set and get an id
*  You can get the debit account code
*  You can get the credit account code
*  You can get the transaction amount
*  You can get the transaction note
*  You can get the transaction datetime


Generated by chippyash/testdox-converter