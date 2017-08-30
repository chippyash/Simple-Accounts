<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts;

/**
 * Interface RecordStatusRecordable
 *
 * Interface for a class that can change record status
 */
interface RecordStatusRecordable
{
    /**
     * Return the record status
     *
     * @param array|null        $key array of record key parts
     *
     * @return RecordStatus
     */
    public function getStatus(array $key = null);

    /**
     * Set the record status
     *
     * @param RecordStatus $status
     * @param array|null   $key array of record key parts
     *
     * @return mixed
     */
    public function setStatus(RecordStatus $status, array $key = null);
}