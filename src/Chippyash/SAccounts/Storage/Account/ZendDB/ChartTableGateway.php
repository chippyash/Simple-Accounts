<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDB;

use Chippyash\Currency\Currency;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\Chart;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Chart of Accounts
 *
 * Table name = sa_coa
 * Columns:
 *   id: int Chart id IDX
 *   name: string Chart Name PK
 *   orgId: int Organisation Id PK
 */
class ChartTableGateway extends TableGateway
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

        parent::__construct('sa_coa', $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Does the table have required COA?
     *
     * @param StringType $coaName
     * @param IntType $orgId
     *
     * @return bool
     */
    public function has(StringType $coaName, IntType $orgId)
    {
        return $this->select(
            [
                'name' => $coaName(),
                'orgId' => $orgId()
            ]
        )->count() == 1;
    }

    /**
     * Create a new COA
     *
     * @param StringType $coaName
     * @param IntType $orgId
     * @param Currency $currency
     *
     * @return int The chart record id
     */
    public function create(StringType $coaName, IntType $orgId, Currency $currency)
    {
        $this->insert(
            [
                'name' => $coaName(),
                'orgId' => $orgId(),
                'crcyCode' => $currency->getCode()->get()
            ]
        );

        return (int) $this->lastInsertValue;
    }

    /**
     * Fetch chart definition from DB
     *
     * @param StringType $name
     *
     * @return Chart
     */
    public function fetchChart(StringType $name)
    {

    }

}