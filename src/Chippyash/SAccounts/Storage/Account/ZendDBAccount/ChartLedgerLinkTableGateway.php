<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDBAccount;

use Chippyash\Type\Number\IntType;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use SAccounts\RecordStatusRecordable;
use SAccounts\RecordStatus;

/**
 * Data model for Chart of Accounts Ledger entries
 *
 * Table name = sa_coa_link
 * Columns:
 *   prnt: int parent of link
 *   child: int child of link
 *
 * @method RecordStatus getStatus(array $key) $key = [prnt=>int, child=>int]
 * @method bool setStatus(RecordStatus $status, array $key) $key = [prnt=>int, child=>int]
 */
class ChartLedgerLinkTableGateway extends TableGateway implements RecordStatusRecordable
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
    public function create(
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
     * Return id of parent ledger to the child
     *
     * Will return 0 if no parent found (i.e. root ledger)
     *
     * @param IntType $child
     *
     * @return IntType
     */
    public function parentOf(IntType $child)
    {
        $result = $this->select(['child' => $child()]);
        if ($result->count() == 0) {
            return new IntType(0);
        }

        return new IntType($result->current()->offsetGet('prnt'));
    }
}