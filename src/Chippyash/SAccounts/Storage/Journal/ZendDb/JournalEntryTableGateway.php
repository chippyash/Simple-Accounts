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
use SAccounts\Nominal;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Journal Entry records
 *
 * Table name = sa_journal
 * Columns:
 *   id: int Entry id PK
 *   jrnId: int Journal Id FK
 *   nominal: Nominal
 *   acDr: Currency
 *   acCr: Currency
 *
 * @method RecordStatus getStatus(array $key) $key = [id=>int]
 * @method bool setStatus(RecordStatus $status, array $key) $key = [id=>int]
 */
class JournalEntryTableGateway extends TableGateway
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

        parent::__construct('sa_journal_entry', $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Does the table have required journal entry record?
     *
     * @param IntType $id
     *
     * @return bool
     */
    public function has(IntType $id)
    {
        return $this->select(['id' => $id()])->count() == 1;
    }

    /**
     * Create a new journal entry record
     *
     * @param IntType   $jrnId    Id of journal header that this record is for
     * @param Nominal   $nominal  Nominal code for entry
     * @param Currency  $acDr     Debit amount
     * @param Currency  $acCr     Credit amount
     *
     * @return int Internal id of record
     */
    public function create(IntType $jrnId, Nominal $nominal, Currency $acDr, Currency $acCr)
    {
        $this->insert(
            [
                'jrnId' => $jrnId(),
                'nominal' => $nominal(),
                'acDr' => $acDr(),
                'acCr' => $acCr()
            ]
        );

        return $this->lastInsertValue;
    }
}