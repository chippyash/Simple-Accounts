<?php
/**
 * Simple Double Entry Accounting

 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;


use Chippyash\Currency\Currency;
use Chippyash\Type\String\StringType;
use MyCLabs\Enum\Enum;

/**
 * Defines account types as a 16 bit number
 *
 * @method static AccountType REAL()
 * @method static AccountType DR()
 * @method static AccountType CR()
 * @method static AccountType ASSET()
 * @method static AccountType BANK()
 * @method static AccountType CUSTOMER()
 * @method static AccountType EXPENSE()
 * @method static AccountType INCOME()
 * @method static AccountType LIABILITY()
 * @method static AccountType EQUITY()
 * @method static AccountType SUPPLIER()
 */
final class AccountType extends Enum
{
    /**
     * A dummy account - used internally, not for public consumption
     */
    const DUMMY     = 0b0000000000000000; //   0
    /**
     * Base of all real accounts - used internally, not for public consumption
     */
    const REAL      = 0b0000000000000001; //   1

    /**
     * Debit account: Balance = dr - cr
     */
    const DR        = 0b0000000000000011; //   3

    /**
     * An account showing assets. Value coming in is DR, going out is CR.
     */
    const ASSET     = 0b0000000000001011; //  11
    /**
     * An account at a bank.  It is a special form of Asset Account
     */
    const BANK      = 0b0000000000011011; //  27
    /**
     * An asset account recording sales to a customer.
     */
    const CUSTOMER  = 0b0000000000101011; //  44
    /**
     * An account showing destination of expenses.  Expense is shown as DR, refund of expense as CR.
     */
    const EXPENSE   = 0b0000000001001011; //  77

    /**
     * Credit account: Balance = cr - dr
     */
    const CR        = 0b0000000000000101; //   5
    /**
     * An account recording liabilities (money owing to third parties.) Liability recorded as CR.
     */
    const LIABILITY = 0b0000000010000101; // 133
    /**
     * An account showing sources of income.  Income is shown as CR, Refund as DR
     */
    const INCOME    = 0b0000000110000101; // 389
    /**
     * An account recording the capital or equity of an organisation.  Positive value is shown as CR, negative as DR.  Essentially a form of Liability as it is owed to the shareholders or owners.
     */
    const EQUITY    = 0b0000001010000101; // 645
    /**
     * A liability account recording details of purchases from Suppliers.
     */
    const SUPPLIER  = 0b0000010010000101; //1157

    /**
     * Debit and Credit column titles
     * @var array
     */
    private $titles = [
        self::DR => ['dr'=>'Debit','cr'=>'Credit'],
        self::CR => ['dr'=>'Debit','cr'=>'Credit'],
        self::ASSET => ['dr'=>'Increase','cr'=>'Decrease'],
        self::BANK => ['dr'=>'Increase','cr'=>'Decrease'],
        self::CUSTOMER => ['dr'=>'Increase','cr'=>'Decrease'],
        self::EXPENSE => ['dr'=>'Expense','cr'=>'Refund'],
        self::INCOME => ['dr'=>'Charge','cr'=>'Income'],
        self::LIABILITY => ['dr'=>'Decrease','cr'=>'Increase'],
        self::EQUITY => ['dr'=>'Decrease','cr'=>'Increase'],
        self::SUPPLIER => ['dr'=>'Decrease','cr'=>'Increase'],
    ];

    /**
     * Return the debit column title for this account type
     *
     * @return StringType
     * @throws AccountsException
     */
    public function drTitle()
    {
        if (!array_key_exists($this->value, $this->titles)) {
            throw new AccountsException('Invalid AccountType for drTitle: ' . $this->value);
        }

        return new StringType($this->titles[$this->value]['dr']);
    }

    /**
     * Return the credit column title for this account type
     *
     * @return StringType
     * @throws AccountsException
     */
    public function crTitle()
    {
        if (!array_key_exists($this->value, $this->titles)) {
            throw new AccountsException('Invalid AccountType for crTitle: ' . $this->value);
        }

        return new StringType($this->titles[$this->value]['cr']);
    }

    /**
     * Return balance of debit and credit amounts
     *
     * @param Currency $dr debit amount
     * @param Currency $cr credit amount
     *
     * @return Currency
     * @throws AccountsException
     */
    public function balance(Currency $dr, Currency $cr)
    {
        if (($this->value & self::DR) == self::DR) {
            //debit account type
            $ret = clone $dr;
            return $ret->set($dr() - $cr());
        } elseif (($this->value & self::CR) == self::CR) {
            //credit account type
            $ret = clone $dr;
            return $ret->set($cr() - $dr());
        } else {
            throw new AccountsException('Cannot determine account type to set balance: ' . $this->value);
        }
    }
}