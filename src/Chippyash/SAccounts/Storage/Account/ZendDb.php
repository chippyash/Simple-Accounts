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
use SAccounts\AccountStorageInterface;
use SAccounts\Chart;
use SAccounts\Organisation;
use SAccounts\Storage\Account\ZendDB\ChartTableGateway;
use SAccounts\Storage\Account\ZendDB\OrgTableGateway;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Ddl\Column\Char;

/**
 * Account chart storage using ZendDb to store in a database
 */
class ZendDb implements AccountStorageInterface
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
     * @param Chart $chart
     *
     * @return bool
     */
    public function send(Chart $chart)
    {
        $this->checkOrgRecord($chart->getOrg());
        $chartId = $this->checkChartRecord($chart);
        $tree = $chart->getTree();

        $tree->accept();
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
     * Check if chart record exists.  Create if it doesn't
     *
     * @param Chart $chart
     *
     * @return int  The internal table id for the chart record
     */
    protected function checkChartRecord(Chart $chart)
    {
        if ($this->chartGW->has($chart->getName(), $chart->getOrg()->getId())) {
            return $chart->getOrg()->getId()->get();
        }

        return $this->chartGW->create($chart->getName(), $chart->getOrg()->getId());
    }
}