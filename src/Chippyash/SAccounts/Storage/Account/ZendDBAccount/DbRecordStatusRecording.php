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
use SAccounts\RecordStatus;

/**
 * Class RecordStatusRecording
 *
 * Trait implementing RecordStatusRecordable interface
 */
trait DbRecordStatusRecording
{
    /**
     * Return the record status
     *
     * @param array|null $key array of record key parts
     *
     * @return RecordStatus
     *
     * @throws StorageException
     */
    public function getStatus(array $key = null)
    {
        if (is_null($key)) {
            throw new StorageException('You must provide keys for DB record to get a Status');
        }
        /** @var ResultSet $result */
        $result = $this->select($key);

        if ($result->count() == 0) {
            throw new StorageException('Record not found');
        }

        return new RecordStatus($result->current()->offsetGet('rowSts'));
    }

    /**
     * Set the record status
     *
     * @param RecordStatus $status
     * @param array|null   $key array of record key parts
     *
     * @return mixed
     *
     * @throws StorageException
     */
    public function setStatus(RecordStatus $status, array $key = null)
    {
        if (is_null($key)) {
            throw new StorageException('You must provide keys for DB record to set a Status');
        }
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