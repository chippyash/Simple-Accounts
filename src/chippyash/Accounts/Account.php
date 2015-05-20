<?php
/**
 * Simple Double Entry Accounting

 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace chippyash\Accounts;

use chippyash\Currency\Currency;
use chippyash\Currency\Factory;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use chippyash\Accounts\AccountType;

/**
 * An Account
 *
 */
class Account
{
    /**
     * Chart that this account belongs to
     *
     * @var Chart
     */
    protected $chart;

    /**
     * Account Id
     *
     * @var Nominal
     */
    protected $id;

    /**
     * Account Type
     *
     * @var AccountType
     */
    protected $type;

    /**
     * Account Name
     *
     * @var StringType
     */
    protected $name;

    /**
     * Account debit amount
     *
     * @var Currency
     */
	protected $acDr;

    /**
     * Account credit amount
     *
     * @var Currency
     */
	protected $acCr;

    public function __construct(Chart $chart, Nominal $id, AccountType $type, StringType $name)
    {
        $this->chart = $chart;
        $this->id = $id;
        $this->type= $type;
        $this->name = $name;
        $currencyClass = $this->chart->getOrg()->getCurrencyCode()->get();
        $this->acDr = Factory::create($currencyClass);
        $this->acCr = Factory::create($currencyClass);
    }

    /**
     * Add to debit amount for this account
     * Will update parent account if required
     *
     * @param Currency $amount
     * @return $this
     */
    public function debit(Currency $amount)
    {
        $this->acDr->set($this->acDr->get() + $amount());
        try {
            $parentId = $this->chart->getParentId($this->id);
        } catch (AccountsException $e) {
            $parentId = null;
        }
        if (!is_null($parentId)) {
            $this->chart->getAccount($parentId)->debit($amount);
        }

        return $this;
    }

    /**
     * Add to credit amount for this account
     * Will update parent account if required
     *
     * @param Currency $amount
     * @return $this
     */
    public function credit(Currency $amount)
    {
        $this->acCr->set($this->acCr->get() + $amount());
        try {
            $parentId = $this->chart->getParentId($this->id);
        } catch (AccountsException $e) {
            $parentId = null;
        }
        if (!is_null($parentId)) {
            $this->chart->getAccount($parentId)->credit($amount);
        }

        return $this;
    }

    /**
     * Return current debit amount
     *
     * @return Currency
     */
    public function getDebit()
    {
        return $this->acDr;
    }

    /**
     * Return current credit amount
     *
     * @return Currency
     */
    public function getCredit()
    {
        return $this->acCr;
    }

    /**
     * Get the account balance
     *
     * Returns the current account balance.
     *
     * @return Currency
     */
    public function getBalance() {
        return $this->type->balance($this->acDr, $this->acCr);
    }

    /**
     * Return account unique id (Nominal Code)
     *
     * @return Nominal
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return account type
     *
     * @return AccountType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return account name
     *
     * @return StringType
     */
    public function getName()
    {
        return $this->name;
    }

//    /**
//     * Check that the account balance is zero
//     *
//     * @return boolean True if zero else false
//     */
//    private function checkZeroBalance() {
//    	$this->setBalance();
//    	if ($this->acNetBal != 0) {
//    		return false;
//    	} else {
//    		return true;
//    	}
//    }
//
//    /**
//     * Can we post to this account
//     *
//     * @return boolean
//     */
//    public function isPostable() {
//    	return ($this->postable === 1);
//    }
    
}
