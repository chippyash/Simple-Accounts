<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;

use chippyash\Type\String\StringType;
use Tree\Node\Node;
use chippyash\Currency\Currency;

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
        $dom = $def->getDefinition();
        $xpath = new \DOMXPath($dom);

        $root = $xpath->query('/chart/account')->item(0);
        $tree = new Node();
        $chart = new Chart($chartName, $org, $tree);

        $this->buildTree($tree, $root, $chart, AccountType::toArray());

        return $chart;
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
     * @param StringType $journalName
     *
     * @return Journal
     * @throws JournalException
     */
    public function fetchJournal(StringType $journalName)
    {
        if (empty($this->journalist)) {
            throw new JournalException(self::ERR1);
        }

        return $this->journalist->readJournal($journalName);
    }

    /**
     * Write a Transaction to the Journal and update the Chart
     *
     * @param Transaction $txn
     * @param Chart $chart
     * @param Journal $journal
     *
     * @return Transaction Transaction with txn Id set
     * @throws AccountsException
     */
    public function writeTransaction(Transaction $txn, Chart $chart, Journal $journal)
    {
        $txn = $journal->write($txn);
        $chart->getAccount($txn->getDrAc())->debit($txn->getAmount());
        $chart->getAccount($txn->getCrAc())->credit($txn->getAmount());

        return $txn;
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
        $attributes = $node->attributes;
        $nominal = new Nominal($attributes->getNamedItem('nominal')->nodeValue);
        $type = new AccountType($accountTypes[strtoupper($attributes->getNamedItem('type')->nodeValue)]);
        $name = new StringType($attributes->getNamedItem('name')->nodeValue);
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