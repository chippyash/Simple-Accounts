<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts\Transaction;

use Monad\Collection;
use Monad\Match;
use SAccounts\AccountType;

/**
 * Collection of Entry
 */
class Entries extends Collection
{
    /**
     * Constructor
     *
     * Enforces Collection to be of type SAccounts\Transaction\Entry
     *
     * @param array $value Associative array of data to set
     */
    public function __construct(array $value = [])
    {
        parent::__construct($value, 'SAccounts\Transaction\Entry');
    }

    /**
     * Returns a new Collection with new entry joined to end of this Collection
     *
     * @param Entry $entry
     *
     * @return Entries
     */
    public function addEntry(Entry $entry)
    {
        return $this->vUnion(static::create([$entry]));
    }

    /**
     * Check balance of entries, returns true if they balance else false
     *
     * @return bool
     */
    public function checkBalance()
    {
        $balance = 0;
        foreach($this->value as $entry) {
            if ($entry->getType()->getValue() == AccountType::DR) {
                $balance -= $entry->getAmount()->get();
            } else {
                $balance += $entry->getAmount()->get();
            }
        }

        return ($balance == 0);
    }

}