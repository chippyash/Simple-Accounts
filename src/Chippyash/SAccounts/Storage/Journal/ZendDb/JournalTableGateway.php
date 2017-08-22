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
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Journal Header records
 *
 * Table name = sa_journal
 * Columns:
 *   id: int Journal id PK
 *   chartId: int Chart Id FK
 *   date: DateTime
 *   note: string
 *
 * @method RecordStatus getStatus(array $key) $key = [id=>int]
 * @method bool setStatus(RecordStatus $status, array $key) $key = [id=>int]
 */
class JournalTableGateway extends TableGateway
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

        parent::__construct('sa_journal', $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Does the table have required journal header record?
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
     * Create a new journal header record
     *
     * @param IntType   $chartId    Id of chart that this record is for
     * @param StringType   $note    Note for entry
     * @param \DateTime|null  $date  Entry date.  Default == now()
     *
     * @return int Internal id of record
     */
    public function create(IntType $chartId, StringType $note, \DateTime $date = null)
    {
        $dt = is_null($date) ? null : $date->format('Y-m-d H:i:s');
        $this->insert(
            [
                'chartId' => $chartId(),
                'note' => $note(),
                'date' => $dt
            ]
        );

        return $this->lastInsertValue;
    }
}