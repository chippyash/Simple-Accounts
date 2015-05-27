<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace chippyash\Accounts;

use chippyash\Currency\Currency;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;

/**
 * A Journal transaction
 */
class Transaction
{
    /**
     * @var IntType
     */
    protected $id = null;

    /**
     * @var Nominal
     */
    protected $drAc;

    /**
     * @var Nominal
     */
    protected $crAc;

    /**
     * @var Currency
     */
    protected $amount;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var StringType
     */
    protected $note;

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
        $this->drAc = $drAc;
        $this->crAc = $crAc;
        $this->amount = $amount;
        if (is_null($note)) {
            $this->note = new StringType('');
        } else {
            $this->note = $note;
        }
        if (is_null($date)) {
            $this->date = new \DateTime();
        } else {
            $this->date = $date;
        }
    }

    /**
     * @param IntType $id
     * @return $this
     */
    public function setId(IntType $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return IntType|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Nominal
     */
    public function getDrAc()
    {
        return $this->drAc;
    }

    /**
     * @return Nominal
     */
    public function getCrAc()
    {
        return $this->crAc;
    }

    /**
     * @return Currency
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return StringType
     */
    public function getNote()
    {
        return $this->note;
    }

}