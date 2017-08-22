<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDB;

/**
 * Interface RecordStatusRecordable
 *
 * Interface for TableGateway class that can change record status
 */
interface RecordStatusRecordable
{
    /**
     * Return the record status
     *
     * @param array        $key array of record key parts
     *
     * @return RecordStatus
     */
    public function getStatus(array $key);

    /**
     * Set the record status
     *
     * @param RecordStatus $status
     * @param array        $key array of record key parts
     *
     * @return mixed
     */
    public function setStatus(RecordStatus $status, array $key);

}