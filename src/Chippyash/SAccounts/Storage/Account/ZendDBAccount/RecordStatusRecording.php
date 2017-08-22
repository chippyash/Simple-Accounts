<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDBAccount;

use SAccounts\Storage\Exceptions\StorageException;
use Zend\Db\ResultSet\ResultSet;

/**
 * Class RecordStatusRecording
 *
 * Trait implementing RecordStatusRecordable interface
 */
trait RecordStatusRecording
{
    /**
     * Return the record status
     *
     * @param array        $key array of record key parts
     *
     * @return RecordStatus
     *
     * @throws StorageException
     */
    public function getStatus(array $key)
    {
        /** @var ResultSet $result */
        $result = $this->select($key);

        if ($result->count() == 0) {
            throw new StorageException('Link record not found');
        }

        return new RecordStatus($result->current()->offsetGet('rowSts'));
    }

    /**
     * Set the record status
     *
     * @param RecordStatus $status
     * @param array        $key array of record key parts
     *
     * @return mixed
     */
    public function setStatus(RecordStatus $status, array $key)
    {
        try {
            return $this->update(
                    [
                        'rowSts' => $status->getValue()
                    ],
                    $key
                ) == 1;

        } catch (\PDOException $e) {
            return false;
        }

    }
}