<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;


use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;

/**
 * Interface to save and fetch a Chart to/from storage
 */
interface JournalStorageInterface
{
    /**
     * Set the journal that we will next be working with
     *
     * @param StringType $name
     *
     * @return $this
     */
    public function setJournalName(StringType $name);

    /**
     * Write Journal definition to store
     *
     * @param Journal $journal
     *
     * @return bool
     */
    public function writeJournal(Journal $journal);

    /**
     * Read journal definition from store
     *
     * @return Journal
     */
    public function readJournal();

    /**
     * Write a transaction to store
     *
     * @param Transaction $transaction
     *
     * @return IntType Transaction Unique Id
     */
    public function writeTransaction(Transaction $transaction);

    /**
     * Read a transaction from store
     *
     * @param IntType $id Transaction Unique Id
     *
     * @return Transaction|null
     */
    public function readTransaction(IntType $id);

    /**
     * Return all transactions for an account from store
     *
     * @param Nominal $nominal Account Nominal code
     *
     * @return array[Transaction,...]
     */
    public function readTransactions(Nominal $nominal);
}