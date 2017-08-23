<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\Account;
use SAccounts\AccountStorageInterface;
use SAccounts\Chart;
use SAccounts\Organisation;
use SAccounts\Storage\Account\ZendDBAccount\ChartLedgerTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\ChartTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\OrgTableGateway;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;
use Zend\Db\Adapter\AdapterInterface;

/**
 * Account chart storage using ZendDb to store in a database
 */
class ZendDbAccount implements AccountStorageInterface, Visitor
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    /**
     * @var OrgTableGateway
     */
    protected $orgGW;
    /**
     * @var ChartTableGateway
     */
    protected $chartGW;
    /**
     * @var ChartLedgerTableGateway
     */
    protected $ledgerGW;

    /**
     * Are we visiting the chart tree to update it?
     *
     * @var bool
     */
    private $visitForUpdate = false;
    /**
     * Are we visiting the chart tree to create it?
     *
     * @var bool
     */
    private $visitForCreate = false;
    /**
     * The current chart id we are processing
     *
     * @var Intype|null
     */
    private $currentChartId = null;

    public function __construct(
        OrgTableGateway $orgGW,
        ChartTableGateway $chartGW,
        ChartLedgerTableGateway $ledgerGW
    ) {
        $this->orgGW = $orgGW;
        $this->chartGW = $chartGW;
        $this->ledgerGW = $ledgerGW;
    }

    /**
     * Fetch a chart from storage
     *
     * @param StringType $name Name of chart
     *
     * @return Chart
     */
    public function fetch(StringType $name)
    {
        // TODO: Implement fetch() method.
    }

    /**
     * Send a chart to storage
     *
     * If the chart does not exist, then its structure and current values will
     * be stored.
     *
     * If the chart exists, then only changes in its structure are saved as you are
     * expected to use the journal in a DB environment to update the chart values
     *
     * @link docs/db-support.sql
     *
     * @param Chart $chart
     *
     * @return bool
     */
    public function send(Chart $chart)
    {
        $org = $chart->getOrg();
        if (!$this->orgGW->has($org->getId())) {
            //create the organisation record
            $orgId = $this->orgGW->create(
                $org->getName(),
                $org->getCurrency(),
                $org->getId()
            );
            //modify chart to use org record with an id that is known
            $chart = new Chart(
                $chart->getName(),
                new Organisation(new IntType($orgId), $org->getName(), $org->getCurrency()),
                $chart->getTree()
            );
        }

        if (!$this->chartGW->has($chart->getName(), $chart->getOrg()->getId())) {
            //create chart with current account balances
            $this->currentChartId = new IntType(
                $this->chartGW->create(
                    $chart->getName(),
                    $chart->getOrg()->getId(),
                    $chart->getOrg()->getCurrency()
                )
            );

            $this->visitForCreate = true;
            $chart->getTree()->accept($this);
            $this->visitForCreate = false;
            $this->currentChartId = null;

            return true;
        }

        //find all new accounts and add them but not their balances
        $this->currentChartId = new IntType(
            $this->chartGW->getIdForChart(
                $chart->getName(),
                $chart->getOrg()->getId()
            )
        );
        $this->visitForUpdate = true;
        $chart->getTree()->accept($this);
        $this->visitForUpdate = false;
        $this->currentChartId = null;

        return true;
    }

    /**
     * @param NodeInterface $node
     *
     * @return mixed
     */
    public function visit(NodeInterface $node)
    {
        if ($this->visitForUpdate) {
            return $this->visitUpdate($node);
        }

        if ($this->visitForCreate) {
            return $this->visitCreate($node);
        }
    }

    /**
     * Visit each node and update database sa_coa_ledger table if required
     *
     * @param NodeInterface $node
     *
     * @return mixed
     */
    private function visitUpdate(NodeInterface $node)
    {
        /** @var Account $account */
        $account = $node->getValue();

        //does the node exist in the chart storage?
        if (!$this->ledgerGW->has($this->currentChartId, $account->getId())) {
            //if not, then create it
            /** @var Account $account */
            $prntAccount = $node->getParent()->getValue();
            $prntAccountId = new IntType(
                $this->ledgerGW->getIdForLedger($this->currentChartId, $prntAccount->getId())
            );

            $this->ledgerGW->create(
                $this->currentChartId,
                $account->getId(),
                $account->getType(),
                $account->getName(),
                $prntAccountId
            );
        }

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }

    /**
     * Visit each node and create a record in sa_coa_ledger table
     *
     * @param NodeInterface $node
     *
     * @return mixed
     */
    private function visitCreate(NodeInterface $node)
    {
        /** @var Account $account */
        $account = $node->getValue();

        //see if we have a parent
        $parent = $node->getParent();
        $prntAccountId = null;
        if (!is_null($parent)) {
            $prntAccountId = new IntType(
                $this->ledgerGW->getIdForLedger($this->currentChartId, $parent->getValue()->getId())
            );
        }

        //create the ledger record and get its internal id
        $ledgerId = $this->ledgerGW->create(
            $this->currentChartId,
            $account->getId(),
            $account->getType(),
            $account->getName(),
            $prntAccountId
        );

        //update the balances for the account
        $this->ledgerGW->update(
            [
                'acDr' => $account->getDebit()->get(),
                'acCr' => $account->getCredit()->get()
            ],
            [
                'id' => $ledgerId
            ]
        );

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}