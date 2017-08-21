<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Ltd, 2017, UK
 * @license   Proprietary See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDB;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\AccountType;
use SAccounts\Nominal;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Chart of Accounts Ledger entries
 *
 * Table name = sa_coa_ledger
 * Columns:
 *   id: int internal id
 *   chartId: int Chart id PK
 *   nominal: string nominal account code PK
 *   type: int account type
 *   name: string account name
 *   acCr: int Credit amount
 *   acDr: int Debit amount
 */
class ChartLedgerTableGateway extends TableGateway
{
    /**
     * @var ChartLedgerLinkTableGateway
     */
    protected $linkGW;

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
        $this->linkGW = new ChartLedgerLinkTableGateway($adapter, $features, $resultSetPrototype, $sql);
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
     * If $prnt is null, then this ledger record is assumed to be the root node
     *
     * @param IntType $chartId Owning chart internal id
     * @param Nominal $nominal Nominal account code
     * @param AccountType $type Type of account
     * @param StringType $name Name of account
     * @param IntType|null $prntId Internal id of parent of this ledger account
     *
     * @return int Internal id
     */
    public function createLedger(
        IntType $chartId,
        Nominal $nominal,
        AccountType $type,
        StringType $name,
        IntType $prntId = null
    ) {
        $this->insert(
            [
                'chartId' => $chartId(),
                'nominal' => $nominal(),
                'type' => $type->getValue(),
                'name' => $name()
            ]
        );

        if (is_null($prntId)) {
            return $this->lastInsertValue;
        }

        $this->linkGW->createLedgerLink($prntId, new IntType($this->lastInsertValue));

        return $this->lastInsertValue;
    }
}