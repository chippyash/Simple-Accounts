<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts\SAccounts\Storage\Journal;

use Chippyash\Currency\Currency;
use Chippyash\Currency\Factory as Crcy;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Journal;
use SAccounts\JournalStorageInterface;
use SAccounts\Nominal;
use SAccounts\Storage\Account\ZendDBAccount\ChartTableGateway;
use SAccounts\Storage\Journal\ZendDBJournal\JournalEntryTableGateway;
use SAccounts\Storage\Journal\ZendDBJournal\JournalTableGateway;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SplitTransaction;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

class ZendDbJournal implements JournalStorageInterface
{
    /**
     * @var JournalTableGateway
     */
    protected $journalGW;
    /**
     * @var JournalEntryTableGateway
     */
    protected $entryGW;
    /**
     * @var integer
     */
    protected $chartId;
    /**
     * @var Currency
     */
    protected $crcy;
    /**
     * @var StringType
     */
    protected $chartName;
    /**
     * @var ChartTableGateway
     */
    protected $chartGW;
    /**
     * @var IntType
     */
    protected $orgId;

    /**
     * ZendDbJournal constructor.
     *
     * @param IntType                  $orgId  Organisation we are filing for
     * @param StringType               $chartName Name of chart we are filing for
     * @param JournalTableGateway      $journalGW
     * @param JournalEntryTableGateway $entryGW
     */
    public function __construct(
        IntType $orgId,
        StringType $chartName,
        JournalTableGateway $journalGW,
        JournalEntryTableGateway $entryGW,
        ChartTableGateway $chartGW
    ) {
        $this->journalGW = $journalGW;
        $this->entryGW = $entryGW;
        $this->chartGW = $chartGW;
        $this->orgId = $orgId;
        $this->setJournalName($chartName);
    }

    /**
     * Write Journal definition to store
     *
     * @param Journal $journal
     *
     * @return bool
     */
    public function writeJournal(Journal $journal)
    {
        //do nothing
        return true;
    }

    /**
     * Read journal definition from store
     *
     * @return Journal
     */
    public function readJournal()
    {
        return new Journal($this->chartName, $this->crcy, $this);
    }

    /**
     * Write a transaction to store
     *
     * @param SplitTransaction $transaction
     *
     * @return IntType Transaction Unique Id
     *
     * @throws AccountsException
     */
    public function writeTransaction(SplitTransaction $transaction)
    {
        if (empty($this->chartId)) {
            throw new AccountsException('Chart id is not set.  Please set journal name');
        }
        if (!$transaction->checkBalance()) {
            throw new AccountsException('Transaction is not balanced. Cannot save');
        }

        $jId = $this->journalGW->insert(
            [
                'chartId' => $this->chartId,
                'note' => $transaction->getNote()->get(),
                'date' => $transaction->getDate()->format('Y-m-d H:i:s')
            ]
        );

        $transaction->setId(new IntType($jId));

        // Send all entries as a single transaction
        $this->entryGW->getAdapter()
            ->getDriver()
            ->getConnection()
            ->beginTransaction();
        /** @var Entry $entry */
        foreach ($transaction->getEntries() as $entry)
        {
            $entryType= $entry->getType()->getValue();
            $amount = $entry->getAmount()->get();
            $acDr = $entryType == AccountType::DR ? $amount : 0;
            $acCr = $entryType == AccountType::CR ? $amount : 0;
            $this->entryGW->insert(
                [
                    'jrnId' => $jId,
                    'nominal' => $entry->getId()->get(),
                    'acDr' => $acDr,
                    'acCr' => $acCr
                ]
            );
        }
        $this->entryGW->getAdapter()
            ->getDriver()
            ->getConnection()
            ->commit();

        return $jId;
    }

    /**
     * Read a transaction from store
     *
     * @param IntType $id Transaction Unique Id
     *
     * @return SplitTransaction|null
     *
     * @throws AccountsException
     */
    public function readTransaction(IntType $id)
    {
        if (empty($this->chartId)) {
            throw new AccountsException('Chart id is not set.  Please set journal name');
        }

        $jrnRecord = $this->journalGW->select(
            [
                'id' => $id
            ]
        );

        if ($jrnRecord->count() == 0) {
            return null;
        }

        $entries = $this->entryGW->select(
            [
                'jrnId' => $id()
            ]
        );

        $txn = (new SplitTransaction(
            new \DateTime($jrnRecord->current()->offsetGet('date')),
            new StringType($jrnRecord->current()->offsetGet('note'))
        ))
        ->setId(new IntType($jrnRecord->current()->offsetGet('id')));

        $crcyCode = $jrnRecord->current()->offsetGet('crcyCode');
        foreach ($entries as $entry) {
            $amount = (int) (((int) $entry['acDr'] == 0) ? $entry['acCr'] : $entry['acDr']);
            $type = ((int) $entry['acDr'] == 0) ? AccountType::CR() : AccountType::DR();
            $txn->addEntry(
                new Entry(
                    new Nominal($entry['nominal']),
                    Crcy::create($crcyCode, $amount),
                    $type
                )
            );
        }

        return $txn;
    }

    /**
     * Return all transactions for an account from store
     *
     * @param Nominal $nominal Account Nominal code
     *
     * @return array[SplitTransaction,...]
     *
     * @throws AccountsException
     */
    public function readTransactions(Nominal $nominal)
    {
        if (empty($this->chartId)) {
            throw new AccountsException('Chart id is not set.  Please set journal name');
        }

        $sql = (new Sql($this->chartGW->getAdapter()));
        $select = $sql
            ->select()
            ->from(['j' => $this->journalGW->getTable()])
            ->columns(['id'])
            ->join(
                ['e' => $this->entryGW->getTable()],
                'j.id = e.jrnId',
                [],
                Select::JOIN_LEFT)
            ->where("e.nominal = '{$nominal()}'");

        $jrnIds = $sql->prepareStatementForSqlObject($select)->execute();

        if ($jrnIds->count() == 0){
            return [];
        }

        $txns = [];
        foreach ($jrnIds as $jrnIdRecord) {
            $txns[] = $this->readTransaction(new IntType($jrnIdRecord['id']));
        }

        return $txns;
    }

    /**
     * Set the journal that we will next be working with
     *
     * @param StringType $name Name of chart
     *
     * @return $this
     */
    public function setJournalName(StringType $name)
    {
        $this->chartName = $name;
        $chart = $this->chartGW->select(
            [
                'orgId' => $this->orgId->get(),
                'name' => $name()
            ]
        )->current();
        $this->chartId = (int) $chart->offsetGet('id');
        $this->crcy = Crcy::create($chart->offsetGet('crcyCode'));

        return $this;
    }
}