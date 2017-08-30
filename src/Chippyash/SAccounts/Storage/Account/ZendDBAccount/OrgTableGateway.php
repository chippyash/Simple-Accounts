<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDBAccount;

use Chippyash\Currency\Currency;
use Chippyash\Currency\Factory as Crcy;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\Organisation;
use SAccounts\RecordStatus;
use SAccounts\RecordStatusRecordable;
use SAccounts\Storage\Exceptions\StorageException;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Organisations
 *
 * Table name = sa_org
 * Columns:
 *   id: int Org id PK
 *   name: string Org Name IDX
 *   crcyCode: string Currency Code for organisation
 *
 * @method RecordStatus getStatus(array $key = null) $key = [id=>int]
 * @method bool setStatus(RecordStatus $status, array $key = null) $key = [id=>int]
 */
class OrgTableGateway extends TableGateway implements RecordStatusRecordable
{
    use DbRecordStatusRecording;

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

        parent::__construct('sa_org', $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Does the table have required organisation?
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
     * Create a new organisation record
     *
     * @param StringType   $name
     * @param Currency   $crcy
     * @param IntType|null $id
     *
     * @return int
     */
    public function create(StringType $name, Currency $crcy, IntType $id = null)
    {
        $id = is_null($id) ? null : $id();
        $this->insert(
            [
                'id' => $id,
                'name' => $name(),
                'crcyCode' => $crcy->getCode()
            ]
        );

        return $this->lastInsertValue;
    }

    /**
     * Return Organisation identified by id
     *
     * @param IntType $id
     *
     * @return Organisation
     *
     * @throws StorageException
     */
    public function getForId(IntType $id)
    {
        $record = $this->select(['id' => $id()]);
        if ($record->count() == 0) {
            throw new StorageException('Organisation not found');
        }

        return new Organisation(
            $id,
            new StringType($record->current()->offsetGet('name')),
            Crcy::create($record->current()->offsetGet('crcyCode'))
        );
    }
}