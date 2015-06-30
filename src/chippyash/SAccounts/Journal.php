<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;
use chippyash\Currency\Currency;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use SAccounts\Transaction\SplitTransaction;

/**
 * A Journal records account Transactions for a Chart
 * It requires a Journalist (JournalStorageInterface) in order to operate
 *
 */
class Journal 
{
    /**
     * @var StringType
     */
    protected $journalName;

    /**
     * @var JournalStorageInterface
     */
    protected $journalist;

    /**
     * Currency in use for this journal
     *
     * @var Currency
     */
    protected $crcy;

    /**
     * Constructor
     *
     * @param StringType $journalName
     * @param Currency $crcy
     * @param JournalStorageInterface $journalist
     */
    public function __construct(StringType $journalName, Currency $crcy, JournalStorageInterface $journalist)
    {
        $this->journalName= $journalName;
        $this->journalist = $journalist;
        $this->crcy = $crcy;
    }

    /**
     * Return name of Journal
     *
     * @return StringType
     */
    public function getName()
    {
        return $this->journalName;
    }

    /**
     * Return currency in use for this Journal
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->crcy;
    }

    /**
     * Write the transaction.
     * Returns transaction with Transaction Id set
     *
     * @param SplitTransaction $transaction
     * @return SplitTransaction
     */
    public function write(SplitTransaction $transaction)
    {
        return $transaction->setId(
                $this->journalist
                    ->writeTransaction($transaction)
            );
    }

    /**
     * Read a specific transaction
     *
     * @param IntType $id
     * @return SplitTransaction
     */
    public function readTransaction(IntType $id)
    {
        return $this->journalist->readTransaction($id);
    }

    /**
     * Read all transactions for an account
     *
     * @param Nominal $nominal
     * @return array [SplitTransaction,...]
     */
    public function readTransactions(Nominal $nominal)
    {
        return $this->journalist->readTransactions($nominal);
    }
}