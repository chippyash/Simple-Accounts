<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Transaction;


use SAccounts\AccountType;
use SAccounts\Nominal;
use chippyash\Currency\Currency;
use chippyash\Type\String\StringType;

/**
 * Simple two entry balanced transaction
 *
 * Only really useful for adding new transactions as any transactions
 * returned from a Journal will be in SplitTransaction form
 */
class SimpleTransaction extends SplitTransaction
{
    /**
     * Constructor
     *
     * @param Nominal $drAc Account to debit
     * @param Nominal $crAc Account to credit
     * @param Currency $amount Transaction amount
     * @param StringType $note Defaults to '' if not set
     * @param \DateTime $date Defaults to today if not set
     */
    public function __construct(Nominal $drAc, Nominal $crAc, Currency $amount, StringType $note = null, \DateTime $date = null)
    {
        parent::__construct($date, $note);
        $this->addEntry(new Entry($drAc, $amount, AccountType::DR()));
        $this->addEntry(new Entry($crAc, $amount, AccountType::CR()));
    }
}