<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Ltd, 2017, UK
 * @license   Proprietary See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDB;

use Chippyash\Currency\Currency;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Nominal;
use Tree\Node\NodeInterface;
use Tree\Visitor\Visitor;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Chart of Accounts Ledger entries
 *
 * Table name = sa_coa_ledger
 * Columns:
 *   chartId: int Chart id PK
 *   nominal: string nominal account code PK
 *   type: int account type
 *   name: string account name
 *   acCr: int Credit amount
 *   acDr: int Debit amount
 */
class ChartLedgerTableGateway extends TableGateway implements Visitor
{
    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param null             $features
     * @param null             $resultSetPrototype
     * @param null             $sql
     */
    public function __construct(
        AdapterInterface $adapter,
        $features = null,
        $resultSetPrototype = null,
        $sql = null
    ) {

        parent::__construct('sa_coa_ledger', $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Does the table have required COA ledger entry?
     *
     * @param IntType $chartId Chart internal id
     * @param Nominal $nominal Nominal account code
     *
     * @return bool
     */
    public function has(IntType $chartId, Nominal $nominal)
    {
        return $this->select(
            [
                'chartId' => $chartId(),
                'nominal' => $nominal()
            ]
        )->count() == 1;
    }

    /**
     * Create a new COA ledger record
     *
     * @param IntType $chartId Chart internal id
     * @param Nominal $nominal Nominal account code
     * @param AccountType $type Type of account
     * @param StringType $name Name of account
     * @param Currency $acDr Debit balance of account
     * @param Currency $acCr Credit balance of account
     *
     * @return bool  Was the record created?
     */
    public function createLedger(
        IntType $chartId,
        Nominal $nominal,
        AccountType $type,
        StringType $name,
        Currency $acDr,
        Currency $acCr
    ) {
        $this->insert(
            [
                'chartId' => $chartId(),
                'nominal' => $nominal(),
                'type' => $type->getValue(),
                'name' => $name(),
                'acDr' => $acDr(),
                'acCr' => $acCr()
            ]
        );

        return true;
    }

    public function createForChart(Chart $chart)
    {
        $chart->getTree()->accept($this);
    }

    /**
     * @param NodeInterface $node
     *
     * @return mixed
     */
    public function visit(NodeInterface $node)
    {
        // TODO: Implement visit() method.
    }


}