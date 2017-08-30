<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use Zend\Db\ResultSet\ResultSet;

/**
 * Class RecordStatusRecording
 *
 * Trait implementing RecordStatusRecordable interface
 */
trait RecordStatusRecording
{
    /**
     * @var RecordStatus
     */
    protected $recordStatus;

    /**
     * Return the record status
     *
     * @param array|null  $key array of record key parts
     *
     * @return RecordStatus
     */
    public function getStatus(array $key = null)
    {
        return $this->recordStatus;
    }

    /**
     * Set the record status
     *
     * @param RecordStatus $status
     * @param array|null   $key array of record key parts
     *
     * @return $this
     *
     * @throws AccountsException
     */
    public function setStatus(RecordStatus $status, array $key = null)
    {
        if (!$this->recordStatus->canChange()) {
            throw new AccountsException('Cannot change status on a defunct record');
        }

        $this->recordStatus= $status;

        return $this;
    }
}