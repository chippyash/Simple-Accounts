<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use Chippyash\Currency\Currency;
use Chippyash\Currency\Factory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Monad\FTry;
use Monad\Match;
use Monad\Option;

/**
 * An Account
 *
 */
class Account implements RecordStatusRecordable, InternallyIdentifiable
{
    use RecordStatusRecording;
    use InternallyIdentifying;

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
    protected $nominal;

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

    /**
     * Account constructor.
     *
     * @param Chart             $chart
     * @param Nominal           $id
     * @param AccountType       $type
     * @param StringType        $name
     * @param IntType           $internalId default = 0
     * @param RecordStatus|null $status default == active
     */
    public function __construct(
        Chart $chart,
        Nominal $nominal,
        AccountType $type,
        StringType $name,
        IntType $internalId = null,
        RecordStatus $status = null
    ) {
        $this->chart = $chart;
        $this->nominal = $nominal;
        $this->type= $type;
        $this->name = $name;
        $currencyClass = $this->chart->getOrg()->getCurrencyCode()->get();
        $this->acDr = Factory::create($currencyClass);
        $this->acCr = Factory::create($currencyClass);
        $this->recordStatus = (is_null($status) ? RecordStatus::ACTIVE() : $status);
        $this->internalId = (is_null($internalId) ? new IntType(0) : $internalId);
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
        Match::on($this->optGetParentId())
            ->Monad_Option_Some(function($parentId) use($amount) {
                $this->chart->getAccount($parentId->value())->debit($amount);
            });

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
        Match::on($this->optGetParentId())
            ->Monad_Option_Some(function($parentId) use($amount) {
                $this->chart->getAccount($parentId->value())->credit($amount);
            });

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
    public function getNominal()
    {
        return $this->nominal;
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

    /**
     * Return Organisation that this account belongs to
     *
     * @return Organisation
     */
    public function getOrg()
    {
        return $this->chart->getOrg();
    }

    /**
     * Return Chart that this account belongs to
     *
     * @return Chart
     */
    public function getChart()
    {
        return $this->chart;
    }

    /**
     * Get parent nominal id as an Option
     *
     * @return Option
     */
    protected function optGetParentId()
    {
        return Match::on(
            FTry::with(
                function () {
                    return $this->chart->getParentId($this->nominal);
                }
            )
        )
            ->Monad_FTry_Success(function ($id) {
                return Option::create($id->flatten());
            })
            ->Monad_FTry_Failure(function () {
                return Option::create(null);
            })
            ->value();
    }
}
