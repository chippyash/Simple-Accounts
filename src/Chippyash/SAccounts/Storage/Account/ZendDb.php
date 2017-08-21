<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Ltd, 2017, UK
 * @license   Proprietary See LICENSE.md
 */
namespace SAccounts\Storage\Account;

use Chippyash\Type\String\StringType;
use SAccounts\Account;
use SAccounts\AccountStorageInterface;
use SAccounts\Chart;
use SAccounts\Organisation;
use SAccounts\Storage\Account\ZendDB\ChartTableGateway;
use SAccounts\Storage\Account\ZendDB\OrgTableGateway;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Ddl\Column\Char;

/**
 * Account chart storage using ZendDb to store in a database
 */
class ZendDb implements AccountStorageInterface, Visitor
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
     * Are we visiting the chart tree for Update?
     *
     * @var bool
     */
    private $visitForUpdate = true;

    public function __construct(
        OrgTableGateway $orgGW,
        ChartTableGateway $chartGW
    ) {
        $this->orgGW = $orgGW;
        $this->chartGW = $chartGW;
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
     * This updates the chart definition, not the current values as that is done
     * by the journal_trigger DB trigger
     *
     * @link docs/db-support.sql
     *
     * @param Chart $chart
     *
     * @return bool
     */
    public function send(Chart $chart)
    {
        $this->visitForUpdate = true;
        $chart->getTree()->accept($this);
        $this->visitForUpdate = false;

        return true;
    }

    /**
     * Checks that organisation record exists.  Create if not found
     *
     * @param Organisation $organisation
     */
    protected function checkOrgRecord(Organisation $organisation)
    {
        $orgId = $organisation->getId();
        if ($this->orgGW->has($orgId)) {
            return;
        }

        $this->orgGW->create(
            $organisation->getName(),
            $organisation->getCurrencyCode(),
            $orgId
        );
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

    }

    /**
     * Visit each node and update database sa_coa table if required
     *
     * @param NodeInterface $node
     *
     * @return mixed
     */
    protected function visitUpdate(NodeInterface $node)
    {
        /** @var Account $account */
        $account = $node->getValue();
        $orgId = $account->getOrg()->getId();

        //Does the chart exist?
        if (!$this->chartGW->has($account->getChart()->getName(), $orgId)) {
            $this->chartGW->create($account->getChart());
            $this->visitForUpdate= false;
            return;
        }

        //does the node exist in the chart storage?

        //if not, then create it
    }

}