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
use Chippyash\Currency\Factory as CFactory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Monad\Match;
use Monad\Option;
use SAccounts\AccountsException;
use SAccounts\AccountType;

/**
 * A Complex Journal transaction type
 */
class SplitTransaction
{
    /**
     * @var IntType
     */
    protected $id = null;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var StringType
     */
    protected $note;

    /**
     * @var Entries
     */
    protected $entries;

    /**
     * Constructor
     *
     * @param \DateTime $date Defaults to today if not set
     * @param StringType $note Defaults to '' if not set
     */
    public function __construct(\DateTime $date = null, StringType $note = null)
    {
        Match::on(Option::create($date))
            ->Monad_Option_Some(function($opt){$this->date = $opt->value();})
            ->Monad_Option_None(function(){$this->date = new \DateTime();});

        Match::on(Option::create($note))
            ->Monad_Option_Some(function($opt){$this->note = $opt->value();})
            ->Monad_Option_None(function(){$this->note = new StringType('');});

        $this->entries = new Entries();
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

    /**
     * Add a transaction entry
     *
     * @param Entry $entry
     *
     * @return $this
     */
    public function addEntry(Entry $entry)
    {
        $this->entries = $this->entries->addEntry($entry);

        return $this;
    }

    /**
     * Do the entries balance?
     *
     * @return bool
     */
    public function checkBalance()
    {
        return $this->entries->checkBalance();
    }

    /**
     * @return Entries
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Get amount if the account is balanced
     *
     * @return Currency
     * @throw AccountsException
     */
    public function getAmount()
    {
        return Match::create(Option::create($this->entries->checkBalance(), false))
            ->Monad_Option_Some(
            function(){
                $tot = 0;
                foreach($this->entries as $entry) {
                    $tot += $entry->getAmount()->get();
                }
                return CFactory::create($entry->getAmount()->getCode()->get())->set($tot / 2);
            })
            ->Monad_Option_None(function(){throw new AccountsException('No amount for unbalanced transaction');})
            ->value();
    }

    /**
     * Return debit account ids
     * return zero, one or more Nominals in an array
     *
     * @return Array[Nominal]
     */
    public function getDrAc()
    {
        $acs = [];
        foreach($this->getEntries() as $entry) {
            if ($entry->getType()->getValue() == AccountType::DR) {
                $acs[] = $entry->getId();
            }
        }

        return $acs;
    }

    /**
     * Return credit account ids
     * return zero, one or more Nominals in an array
     *
     * @return Array[Nominal]
     */
    public function getCrAc()
    {
        $acs = [];
        foreach($this->getEntries() as $entry) {
            if ($entry->getType()->getValue() == AccountType::CR) {
                $acs[] = $entry->getId();
            }
        }

        return $acs;
    }

    /**
     * Is this a simple transaction, i.e. 1 dr and 1 cr entry
     *
     * @return bool
     */
    public function isSimple()
    {
        return (count($this->getDrAc()) == 1
            && count($this->getCrAc()) == 1);
    }

}