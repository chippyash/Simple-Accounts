<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts\Helper;

use Chippyash\Type\BoolType;
use Chippyash\Type\String\StringType;
use Monad\FTry;
use Monad\Match;
use Monad\Option;
use SAccounts\Control\Links;
use SAccounts\Chart;

/**
 * A helper to close out accounts, usually at year end
 */
class AccountCloser
{
    /**
     * Default closing account transaction note template
     */
    const DEF_NOTE_TPL = 'Closing account: %s';

    /**
     * @var Chart
     */
    protected $chart;
    /**
     * @var DateTime
     */
    protected $dt;
    /**
     * @var Links
     */
    protected $controls;
    /**
     * @var boolean
     */
    protected $justIAndE;
    /**
     * @var Journal
     */
    protected $journal;
    /**
     * @var string
     */
    protected $noteTemplate;

    /**
     * Close out accounts
     *
     * @param Chart $chart Chart to use
     * @param Links $controlAccounts ControlAccount links to be used to direct the closing operation
     * @param \DateTime $dt [default = now()] Datetime to close the accounts
     * @param BoolType $justIAndE [default = true], Close just income and expense accounts or close asset accounts as well
     */
    public function closeAccounts(
        Chart $chart,
        Links $controlAccounts,
        \DateTime $dt = null,
        BoolType $justIAndE = null)
    {
        $this->chart = $chart;
        $this->controls = $controlAccounts;

        $this->dt = Match::create(Option::create($dt))
            ->Monad_Option_Some(function($dt) {return $dt;})
            ->Monad_Option_None(function() {return new \DateTime();})
            ->value();

        $this->justIAndE = Match::create(Option::create($justIAndE))
            ->Monad_Option_Some(function($bool) {return $bool();})
            ->Monad_Option_None(function() {return true;})
            ->value();

//        $this->journal = Match::create(Option::create($journalName))
//            ->Monad_Option_Some(function($name) use($accountant) {return $accountant->fetchJournal($name);})
//            ->Monad_Option_None(function() {return null;})
//            ->value();
//
//        $this->noteTemplate = Match::create(Option::create($noteTemplate))
//            ->Monad_Option_Some(function($tpl) {return $tpl();})
//            ->Monad_Option_None(function() {return self::DEF_NOTE_TPL;})
//            ->value();

        return $this->closeTheAccounts();
    }

    protected function closeTheAccounts()
    {
        $this->checkControlAccounts();
    }

    protected function checkControlAccounts()
    {
        $controls = $this->controls;
        $chart = $this->chart;

        $hasIncome = Option::create(
            function() use($chart, $controls) {
                return $chart->hasAccount($controls->getLinkId(new StringType('income')));
            },
            false
        )
        ->value();
        $hasIncome = $chart->hasAccount($controls->getLinkId(new StringType('income')));;

        $hasExpense = Option::create(
            function() use($chart, $controls) {
                return $chart->hasAccount($controls->getLinkId(new StringType('expense')));
            },
            false
        );
        $hasClose = Option::create(
            function() use($chart, $controls) {
                return $chart->hasAccount($controls->getLinkId(new StringType('close')));
            },
            false
        );
        $hasAsset = Match::on(Option::create($this->justIAndE, true))
            ->Monad_Option_Some(function() use($chart, $controls) {
                return Option::create(
                    function() use($chart, $controls) {
                        return $chart->hasAccount($controls->getLinkId(new StringType('asset')));
                    },
                    false
                );
            })
            ->Monad_Option_None(function($none) {return $none;})
            ->value();

        $hasLiability = Match::on(Option::create($this->justIAndE, true))
            ->Monad_Option_Some(function() use($chart, $controls) {
                return Option::create(
                    function() use($chart, $controls) {
                        return $chart->hasAccount($controls->getLinkId(new StringType('liability')));
                    },
                    false
                );
            })
            ->Monad_Option_None(function($none) {return $none;})
            ->value();


    }
}