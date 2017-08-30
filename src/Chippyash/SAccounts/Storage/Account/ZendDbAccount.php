<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account;

use Chippyash\Currency\Factory as Crcy;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\Account;
use SAccounts\AccountStorageInterface;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Nominal;
use SAccounts\Organisation;
use SAccounts\RecordStatus;
use SAccounts\Storage\Account\ZendDBAccount\ChartLedgerLinkTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\ChartLedgerTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\ChartTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\OrgTableGateway;
use SAccounts\Storage\Exceptions\StorageException;
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
     * @var ChartLedgerLinkTableGateway
     */
    protected $linkGW;

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
    /**
     * The current chart we are processing
     *
     * @var Chart|null
     */
    private $currentChart = null;

    public function __construct(
        OrgTableGateway $orgGW,
        ChartTableGateway $chartGW,
        ChartLedgerTableGateway $ledgerGW,
        ChartLedgerLinkTableGateway $linkGW
    ) {
        $this->orgGW = $orgGW;
        $this->chartGW = $chartGW;
        $this->ledgerGW = $ledgerGW;
        $this->linkGW = $linkGW;
    }

    /**
     * Fetch a chart from storage
     *
     * @param StringType $name Name of chart
     * @param IntType $orgId that the chart belongs to
     *
     * @return Chart
     *
     * @throws StorageException
     */
    public function fetch(StringType $name, IntType $orgId)
    {
        $orgRecord = $this->orgGW->select(['id' => $orgId()])->current();
        if(is_null($orgRecord)) {
            throw new StorageException('Organisation not found');
        }
        $org = new Organisation(
            $orgId,
            new StringType($orgRecord->offsetGet('name')),
            Crcy::create($orgRecord->offsetGet('crcyCode'))
        );

        $chart = new Chart($name, $org);
        $chartId = $this->chartGW->getIdForChart($name, $orgId);

        foreach ($this->ledgerGW->select(['chartId' => $chartId]) as $accountRecord) {
            $prntId = $this->linkGW->parentOf(
                new IntType($accountRecord->offsetGet('id'))
            )->get();
            $prntNominal =null;
            if ($prntId != 0) {
                $prntNominal = new Nominal(
                        $this->ledgerGW->select(
                        [
                            'id' => $prntId
                        ]
                    )->current()
                    ->offsetGet('nominal')
                );
            }

            $acType = AccountType::search((int) $accountRecord->offsetGet('type'));
            $status = RecordStatus::search($accountRecord->offsetGet('rowSts'));
            $chart->addAccount(
                new Account(
                    $chart,
                    new Nominal($accountRecord->offsetGet('nominal')),
                    AccountType::$acType(),
                    new StringType($accountRecord->offsetGet('name')),
                    new IntType($accountRecord->offsetGet('id')),
                    RecordStatus::$status()
                ),
                $prntNominal
            );
        }

        return $chart;
    }

    /**
     * Send a chart to storage
     *
     * If the chart does not exist, then its structure and current values will
     * be stored. This allows for initial chart creation.
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
        if (!$this->orgGW->has($org->id())) {
            //create the organisation record
            $orgId = $this->orgGW->create(
                $org->getName(),
                $org->getCurrency(),
                $org->id()
            );
            //modify chart to use org record with an id that is known
            $chart = new Chart(
                $chart->getName(),
                new Organisation(new IntType($orgId), $org->getName(), $org->getCurrency()),
                $chart->getTree()
            );
        }

        if (!$this->chartGW->has($chart->getName(), $chart->getOrg()->id())) {
            //create chart with current account balances
            $this->currentChartId = new IntType(
                $this->chartGW->create(
                    $chart->getName(),
                    $chart->getOrg()->id(),
                    $chart->getOrg()->getCurrency()
                )
            );

            $this->currentChart= $chart;
            $this->visitForCreate = true;
            $chart->getTree()->accept($this);
            $this->visitForCreate = false;
            $this->currentChartId = null;
            $this->currentChart = null;

            return true;
        }

        //find all new accounts and add them but not their balances
        $this->currentChartId = new IntType(
            $this->chartGW->getIdForChart(
                $chart->getName(),
                $chart->getOrg()->id()
            )
        );
        $this->currentChart= $chart;
        $this->visitForUpdate = true;
        $chart->getTree()->accept($this);
        $this->visitForUpdate = false;
        $this->currentChartId = null;
        $this->currentChart= null;

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
        if (!$this->ledgerGW->has($this->currentChartId, $account->getNominal())) {
            //if not, then create it
            /** @var Account $account */
            $prntAccount = $node->getParent()->getValue();
            $prntAccountId = new IntType(
                $this->ledgerGW->getIdForLedger($this->currentChartId, $prntAccount->getNominal())
            );

            $internalId = ($account->id()->get() == 0 ? null : $account->id());
            $ledgerId = $this->ledgerGW->create(
                $this->currentChartId,
                $account->getNominal(),
                $account->getType(),
                $account->getName(),
                $internalId,
                $prntAccountId
            );

            //save account with its new internal id into tree
            $node->setValue(
                new Account(
                    $this->currentChart,
                    $account->getNominal(),
                    $account->getType(),
                    $account->getName(),
                    new IntType($ledgerId),
                    $account->getStatus()
                )
            );
        }

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }

    /**
     * Visit each node and create a record in sa_coa_ledger table
     * but do not record any balance information
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
                $this->ledgerGW->getIdForLedger($this->currentChartId, $parent->getValue()->getNominal())
            );
        }

        //create the ledger record and get its internal id
        $internalId = ($account->id()->get() == 0 ? null : $account->id());
        $ledgerId = $this->ledgerGW->create(
            $this->currentChartId,
            $account->getNominal(),
            $account->getType(),
            $account->getName(),
            $internalId,
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

        //save account with its new internal id into tree
        $node->setValue(
            new Account(
                $this->currentChart,
                $account->getNominal(),
                $account->getType(),
                $account->getName(),
                new IntType($ledgerId),
                $account->getStatus()
            )
        );

        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}