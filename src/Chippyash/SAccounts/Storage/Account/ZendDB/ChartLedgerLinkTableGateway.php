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
use SAccounts\Storage\Exceptions\StorageException;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;

/**
 * Data model for Chart of Accounts Ledger entries
 *
 * Table name = sa_coa_link
 * Columns:
 *   prnt: int parent of link
 *   child: int child of link
 */
class ChartLedgerLinkTableGateway extends TableGateway
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

        parent::__construct('sa_coa_link', $adapter, $features, $resultSetPrototype, $sql);
    }

    /**
     * Create a new COA ledger link record
     *
     * @param IntType $prnt
     * @param IntType $child
     *
     * @return boolean
     */
    public function createLedgerLink(
        IntType $prnt,
        IntType $child
    ) {
        $this->insert(
            [
                'prnt' => $prnt(),
                'child' => $child()
            ]
        );

        return true;
    }

    /**
     * Set the record status
     *
     * @param IntType      $prnt
     * @param IntType      $child
     * @param RecordStatus $status
     *
     * @return bool True on success else false
     */
    public function setStatus(
        IntType $prnt,
        IntType $child,
        RecordStatus $status
    ) {
        try {
            return $this->update(
                [
                    'rowSts' => $status->getValue()
                ],
                [
                    'prnt' => $prnt(),
                    'child' => $child()
                ]
            ) == 1;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return the record status
     *
     * @param IntType $prnt
     * @param IntType $child
     *
     * @return RecordStatus
     *
     * @throws StorageException
     */
    public function getStatus(
        IntType $prnt,
        IntType $child
    ) {
        /** @var ResultSet $result */
        $result = $this->select(
            [
                'prnt' => $prnt(),
                'child' => $child()
            ]
        );

        if ($result->count() == 0) {
            throw new StorageException('Link record not found');
        }

        return new RecordStatus($result->current()->offsetGet('rowSts'));
    }

}