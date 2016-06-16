<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts\Transaction;

use Chippyash\Currency\Currency;
use Monad\FTry;
use Monad\Match;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Nominal;

/**
 * Records a transaction value entry for a ledger
 */
class Entry
{
    /**
     * Exception error message
     */
    const ERR_NOTYPE = 'Account type must be DR or CR';

    /**
     * @var Nominal
     */
    protected $entryId;

    /**
     * @var Currency
     */
    protected $amount;

    /**
     * @var AccountType
     */
    protected $type;

    /**
     * @param Nominal $entryId
     * @param Currency $amount
     * @param AccountType $type
     *
     * @throws AccountsException
     */
    public function __construct(Nominal $entryId, Currency $amount, AccountType $type)
    {
        $this->entryId = $entryId;
        $this->amount = $amount;
        $this->type = $this->checkType($type)
            ->pass()
            ->value();
    }

    /**
     * @return Nominal
     */
    public function getId()
    {
        return $this->entryId;
    }

    /**
     * @return Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return AccountType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param AccountType $type
     * @return FTry
     */
    protected function checkType(AccountType $type)
    {
        return Match::on($type->getValue())
            ->test(AccountType::CR, function () {
                return FTry::with(AccountType::CR());
            })
            ->test(AccountType::DR, function () {
                return FTry::with(AccountType::DR());
            })
            ->any(function () {
                return FTry::with(new AccountsException(self::ERR_NOTYPE));
            })
            ->value();
    }
}
