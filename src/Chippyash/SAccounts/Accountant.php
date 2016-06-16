<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;

use Assembler\FFor;
use Chippyash\Type\String\StringType;
use SAccounts\Transaction\SplitTransaction;
use Tree\Node\Node;
use Chippyash\Currency\Currency;

/**
 * An Accountant helps with the accounts
 */
class Accountant 
{
    /**@+
     * Error strings
     */
    const ERR1 = 'Journalist not set';
    const ERR2 = 'Cannot file the Journal';
    const ERR3 = 'Cannot file the Chart';
    /**@-*/

    /**
     * @var AccountStorageInterface
     */
    protected $fileClerk;

    /**
     * @var JournalStorageInterface
     */
    protected $journalist = null;

    public function __construct(AccountStorageInterface $fileClerk)
    {
        $this->fileClerk = $fileClerk;
    }

    /**
     * Create a new Chart
     *
     * @param StringType $chartName
     * @param Organisation $org
     * @param ChartDefinition $def
     *
     * @return Chart
     */
    public function createChart(StringType $chartName, Organisation $org, ChartDefinition $def)
    {
        return FFor::create()
            ->dom(function() use($def) {
                return $def->getDefinition();
            })
            ->xpath(function($dom){
                return new \DOMXPath($dom);
            })
            ->root(function($xpath){
                return $xpath->query('/chart/account')->item(0);
            })
            ->tree(function(){
                return new Node();
            })
            ->chart(function($tree) use ($chartName, $org){
                return new Chart($chartName, $org, $tree);
            })
            ->build(function($root, $tree, $chart){
                $this->buildTree($tree, $root, $chart, AccountType::toArray());
            })
            ->fyield('chart');
    }

    /**
     * Store a chart
     *
     * @param Chart $chart
     *
     * @return $this
     * @throws AccountsException
     */
    public function fileChart(Chart $chart)
    {
        if(!$this->fileClerk->send($chart)) {
            throw new AccountsException(self::ERR3);
        }
        return $this;
    }

    /**
     * Fetch a chart from storage
     *
     * @param StringType $chartName
     *
     * @return Chart
     */
    public function fetchChart(StringType $chartName)
    {
        return $this->fileClerk->fetch($chartName);
    }

    /**
     * Set the Journalist to be used for managing Journal storage
     *
     * @param JournalStorageInterface $journalist
     * @return $this
     */
    public function setJournalist(JournalStorageInterface $journalist)
    {
        $this->journalist = $journalist;
        return $this;
    }

    /**
     * Create and return a new Journal for a Chart
     *
     * @param StringType $journalName
     * @param Currency $crcy
     *
     * @return Journal
     * @throws JournalException
     */
    public function createJournal(StringType $journalName, Currency $crcy)
    {
        if (empty($this->journalist)) {
            throw new JournalException(self::ERR1);
        }

        return new Journal($journalName, $crcy, $this->journalist);
    }

    /**
     * Store a Journal
     *
     * @param Journal $journal
     *
     * @return $this
     * @throws JournalException
     */
    public function fileJournal(Journal $journal)
    {
        if (empty($this->journalist)) {
            throw new JournalException(self::ERR1);
        }

        if (!$this->journalist->writeJournal($journal)) {
            throw new JournalException(self::ERR2);
        }

        return $this;
    }

    /**
     * Fetch Journal from store
     *
     * @return Journal
     * @throws JournalException
     */
    public function fetchJournal()
    {
        if (empty($this->journalist)) {
            throw new JournalException(self::ERR1);
        }

        return $this->journalist->readJournal();
    }

    /**
     * Write a Transaction to the Journal and update the Chart
     *
     * @param SplitTransaction $txn
     * @param Chart $chart
     * @param Journal $journal
     *
     * @return SplitTransaction Transaction with txn Id set
     * @throws AccountsException
     */
    public function writeTransaction(SplitTransaction $txn, Chart $chart, Journal $journal)
    {
        return FFor::create()
            ->txn(function() use ($journal, $txn) {return $journal->write($txn);})
            ->chart(function($txn) use ($chart) {
                $chart->getAccount($txn->getDrAc()[0])->debit($txn->getAmount());
                $chart->getAccount($txn->getCrAc()[0])->credit($txn->getAmount());
            })
            ->fyield('txn');
    }

    /**
     * Recursively build chart of account tree
     *
     * @param Node $tree
     * @param \DOMNode $node
     * @param Chart $chart
     * @param array $accountTypes
     */
    protected function buildTree(Node $tree, \DOMNode $node, Chart $chart, array $accountTypes)
    {
        //create current node
        list($nominal, $type, $name) = FFor::create()
            ->attributes(function() use($node) {return $node->attributes;})
            ->nominal(function($attributes){return new Nominal($attributes->getNamedItem('nominal')->nodeValue);})
            ->name(function($attributes){return new StringType($attributes->getNamedItem('name')->nodeValue);})
            ->type(function($attributes) use ($accountTypes){return new AccountType($accountTypes[strtoupper($attributes->getNamedItem('type')->nodeValue)]);})
            ->fyield('nominal', 'type', 'name');

        $tree->setValue(new Account($chart, $nominal, $type, $name));

        //recurse through sub accounts
        foreach($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement) {
                $childTree = new Node();
                $tree->addChild($childTree);
                $this->buildTree($childTree, $childNode, $chart, $accountTypes);
            }
        }
    }
}