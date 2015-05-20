<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Accounts;

use chippyash\Type\String\StringType;
use Tree\Node\Node;

/**
 * An Accountant helps with the accounts
 */
class Accountant 
{
    /**
     * @var AccountStorageInterface
     */
    protected $fileClerk;

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
     */
    public function fileChart(Chart $chart)
    {
        $this->fileClerk->send($chart);
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