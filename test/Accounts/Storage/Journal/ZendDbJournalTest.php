<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Limited, 2017, UK
 * @license   GPL 3.0 See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Journal;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\AccountType;
use SAccounts\Journal;
use SAccounts\Nominal;
use SAccounts\Storage\Account\ZendDBAccount\ChartTableGateway;
use SAccounts\Storage\Journal\ZendDbJournal;
use SAccounts\Storage\Journal\ZendDbJournal\JournalEntryTableGateway;
use SAccounts\Storage\Journal\ZendDbJournal\JournalTableGateway;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SimpleTransaction;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Chippyash\Currency\Factory as Crcy;

class ZendDbJournalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;

    /**
     * System under test
     * @var ZendDbJournal
     */
    protected $sut;

    /**
     * @var IntType
     */
    protected $orgId;
    /**
     * @var StringType
     */
    protected $chartName;
    /**
     * @var ChartTableGateway
     */
    protected $chartGW;
    /**
     * @var JournalTableGateway
     */
    protected $journalGW;
    /**
     * @var JournalEntryTableGateway
     */
    protected $entryGW;

    protected function setUp()
    {
        $this->orgId = new IntType(10);
        $this->chartName = new StringType('Test');
        $this->chartGW = new ChartTableGateway(self::$zendAdapter);
        $this->journalGW = new JournalTableGateway(self::$zendAdapter);
        $this->entryGW = new JournalEntryTableGateway(self::$zendAdapter);

        $this->sut = new ZendDbJournal(
            $this->orgId,
            $this->chartName,
            $this->journalGW,
            $this->entryGW,
            $this->chartGW
        );
    }

    protected function tearDown()
    {
        $this->journalGW->delete([]);
        $this->entryGW->delete([]);
    }

    public function testWritingToTheJournalReturnsTrue()
    {
        $journal = new Journal(
            $this->chartName,
            Crcy::create('GBP', 0),
            $this->sut
        );
        $this->assertTrue($this->sut->writeJournal($journal));
    }

    public function testReadingTheJournalReturnsAJournal()
    {
        $journal= $this->sut->readJournal();

        $this->assertInstanceOf('SAccounts\Journal', $journal);
        $this->assertEquals('Test', $journal->getName()->get());
    }

    public function testWritingATransactionWillReturnATransactionId()
    {
        $txnId = $this->sut->writeTransaction($this->createTransaction('0000','00001',12.26));
        $this->assertEquals(1, $txnId());
    }

    /**
     * @expectedException \SAccounts\AccountsException
     * @expectedExceptionMessage Transaction is not balanced. Cannot save
     */
    public function testYouCannotWriteAnUnbalancedTransaction()
    {
        $txn = $this->createTransaction('0000','00001',12.26);
        $txn->addEntry(new Entry(new Nominal('00002'),Crcy::create('GBP', 14), AccountType::DR()));
        $this->sut->writeTransaction($txn);
    }

    public function testWritingTransactionsWillAddRecordsToTheDatabase()
    {
        $txnId = $this->sut->writeTransaction($this->createTransaction('0000','00001',12.26));
        $this->assertEquals(1, $this->journalGW->select(['id'=>$txnId()])->count());
        $this->assertEquals(2, $this->entryGW->select(['jrnId'=>$txnId()])->count());
    }

    public function testYouCanRetrieveAJournalEntryByItsTransactionId()
    {
        $txnId = $this->sut->writeTransaction($this->createTransaction('0000','00001',12.26));
        $txn = $this->sut->readTransaction($txnId);
        $this->assertInstanceOf('Saccounts\Transaction\SplitTransaction', $txn);
    }

    public function testYouCanFetchAllTransactionsForANominalCode()
    {
        $this->sut->writeTransaction($this->createTransaction('0000','00001',12.26));
        $this->sut->writeTransaction($this->createTransaction('2000','00001',13.74));

        $txns = $this->sut->readTransactions(new Nominal('00001'));
        $this->assertEquals(2, count($txns));
        $txns = $this->sut->readTransactions(new Nominal('0000'));
        $this->assertEquals(1, count($txns));
        $txns = $this->sut->readTransactions(new Nominal('2000'));
        $this->assertEquals(1, count($txns));
    }

    /**
     * Set up SQLite database on real file system as it doesn't
     * support streams and cannot therefore use VFSStream
     *
     * @link https://bugs.php.net/bug.php?id=55154
     */
    public static function setUpBeforeClass()
    {
        self::$zendAdapter = $db = new DbAdapter(
            [
                'driver' => 'Pdo_sqlite',
                'database' => __DIR__ . '/resources/sqlite.db'
            ]
        );

        //Journal table
        $ddl = <<<EOF
create table if NOT EXISTS sa_journal (
  id INTEGER primary key,
  chartId INTEGER UNSIGNED not null,
  note TEXT not null,
  date DATETIME default CURRENT_TIMESTAMP,
  ref INT UNSIGNED null
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_journal', DbAdapter::QUERY_MODE_EXECUTE);

        //Journal entries table
        $ddl = <<<EOF
create table if not exists sa_journal_entry (
  id integer primary key,
  jrnId int unsigned null,
  nominal TEXT not null,
  acDr INTEGER default 0,
  acCr INTEGER default 0 
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_journal_entry', DbAdapter::QUERY_MODE_EXECUTE);

        //COA table
        $ddl = <<<EOF
create table if NOT EXISTS sa_coa (
  id INTEGER primary key,
  orgId INTEGER not null,
  name TEXT not null,
  crcyCode TEXT default 'GBP' not null,
  rowDt DATETIME DEFAULT CURRENT_TIMESTAMP,
  rowSts TEXT DEFAULT 'active',
  rowUid INTEGER DEFAULT 0
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa', DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('insert into sa_coa (orgId, crcyCode, name) VALUES (10, \'GBP\', \'Test\')', DbAdapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Remove sqlite db
     */
    public static function tearDownAfterClass()
    {
        self::$zendAdapter = null;
        unlink(__DIR__ . '/resources/sqlite.db');
    }

    /**
     * Create and return a test transaction
     *
     * @param $dr
     * @param $cr
     * @param $amount
     * @param null $note
     *
     * @return SimpleTransaction
     */
    protected function createTransaction($dr, $cr, $amount, $note = null)
    {
        $crcy = Crcy::create('gbp', $amount);
        if (is_null($note)) {
            $txn = new SimpleTransaction(new Nominal($dr), new Nominal($cr), $crcy);
        } else {
            $txn = new SimpleTransaction(new Nominal($dr), new Nominal($cr), $crcy, new StringType($note));
        }

        return $txn;
    }
}
