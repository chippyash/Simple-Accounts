<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Account;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use org\bovigo\vfs\vfsStream;
use SAccounts\Account;
use SAccounts\Accountant;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\ChartDefinition;
use SAccounts\Nominal;
use SAccounts\Organisation;
use SAccounts\Storage\Account\ZendDbAccount;
use SAccounts\Storage\Account\ZendDBAccount\ChartLedgerLinkTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\ChartLedgerTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\ChartTableGateway;
use SAccounts\Storage\Account\ZendDBAccount\OrgTableGateway;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Chippyash\Currency\Factory as Crcy;

class ZendDbAccountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    protected static $zendAdapter;

    /**
     * System Under Test
     *
     * @var ZendDBAccount
     */
    protected $sut;
    /**
     * @var Organisation
     */
    protected $org;
    /**
     * @var Accountant
     */
    protected $accountant;
    /**
     * @var Chart
     */
    protected $chart;
    /**
     * @var OrgTableGateway
     */
    protected $orgGW;
    /**
     * @var ChartTableGateway
     */
    protected $chartGW;
    /**
     * @var ChartLedgerTableGateway
     */
    protected $ledgerGW;
    /**
     * @var ChartLedgerLinkTableGateway
     */
    protected $linkGW;

    protected function setUp()
    {
        $this->orgGW = new OrgTableGateway(self::$zendAdapter);
        $this->chartGW = new ChartTableGateway(self::$zendAdapter);
        $this->ledgerGW = new ChartLedgerTableGateway(self::$zendAdapter);
        $this->linkGW = new ChartLedgerLinkTableGateway(self::$zendAdapter);

        $this->sut = new ZendDbAccount(
            $this->orgGW,
            $this->chartGW,
            $this->ledgerGW,
            $this->linkGW
        );
        $this->org = new Organisation(new IntType(1), new StringType('Test'), Crcy::create('gbp'));
        $this->accountant= new Accountant($this->sut);

        $root = vfsStream::setup();
        vfsStream::create(
            [
                'def.xml' => $this->chartDefinition()
            ],
            $root
        );

        $def = new ChartDefinition(new StringType($root->url() . '/def.xml'));
        $this->chart = $this->accountant->createChart(
            new StringType('Test'),
            $this->org,
            $def
        );
    }

    protected function tearDown()
    {
        $db = self::$zendAdapter;
        $db->query('delete from sa_org', DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa', DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa_ledger', DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa_link', DbAdapter::QUERY_MODE_EXECUTE);
    }

    public function testYouCanSendANewChartToStorageAndItWillStoreAccountBalances()
    {
        $this->chart->getAccount(new Nominal('7100'))
            ->debit(Crcy::create('GBP', 10));
        $this->chart->getAccount(new Nominal('2100'))
            ->credit(Crcy::create('GBP', 10));

        $this->assertTrue($this->sut->send($this->chart));

        $balance = $this->ledgerGW->select(['nominal' => '0000'])->toArray()[0];
        $this->assertEquals(1000, $balance['acDr']);
        $this->assertEquals(1000, $balance['acCr']);

        $balance = $this->ledgerGW->select(['nominal' => '7100'])->toArray()[0];
        $this->assertEquals(1000, $balance['acDr']);
        $this->assertEquals(0, $balance['acCr']);

        $balance = $this->ledgerGW->select(['nominal' => '2100'])->toArray()[0];
        $this->assertEquals(0, $balance['acDr']);
        $this->assertEquals(1000, $balance['acCr']);

        $this->assertEquals(21, $this->linkGW->select()->count());
    }

    public function testYouCanSendAnAmendedChartToStorageAndItWillNotStoreAccountBalances()
    {
        //set up original chart with balances
        $this->chart->getAccount(new Nominal('7100'))
            ->debit(Crcy::create('GBP', 10));
        $this->chart->getAccount(new Nominal('2100'))
            ->credit(Crcy::create('GBP', 10));

        $this->assertTrue($this->sut->send($this->chart));

        //add an account and new balances
        $this->chart->addAccount(
            new Account(
                $this->chart,
                new Nominal('7110'),
                AccountType::EXPENSE(),
                new StringType('Utilities')
            ),
            new Nominal('7100')
        )->getAccount(new Nominal('7110'))
            ->credit(Crcy::create('GBP', 30));
        $this->chart->getAccount(new Nominal('2100'))
            ->debit(Crcy::create('GBP', 30));

        //and send the amended chart
        $this->assertTrue($this->sut->send($this->chart));

        //balances should be as per before adding the new account
        $balance = $this->ledgerGW->select(['nominal' => '0000'])->toArray()[0];
        $this->assertEquals(1000, $balance['acDr']);
        $this->assertEquals(1000, $balance['acCr']);

        $balance = $this->ledgerGW->select(['nominal' => '7100'])->toArray()[0];
        $this->assertEquals(1000, $balance['acDr']);
        $this->assertEquals(0, $balance['acCr']);

        $balance = $this->ledgerGW->select(['nominal' => '2100'])->toArray()[0];
        $this->assertEquals(0, $balance['acDr']);
        $this->assertEquals(1000, $balance['acCr']);

        //even though we set a balance on new account it hasn't been recorded
        $balance = $this->ledgerGW->select(['nominal' => '2110'])->toArray()[0];
        $this->assertEquals(0, $balance['acDr']);
        $this->assertEquals(0, $balance['acCr']);

        //but we have a new link
        $this->assertEquals(22, $this->linkGW->select()->count());
    }

    public function testYouCanFetchAnExistingChart()
    {
        $this->assertTrue($this->sut->send($this->chart));

        /** @var Chart $chart */
        $chart = $this->sut->fetch(new StringType('Test'), new IntType(1));

        $this->assertInstanceOf('\SAccounts\Chart', $chart);

        $tree = $chart->getTree();
        $this->assertTrue($tree->isRoot());
        //first element of root is the COA head account
        $this->assertEquals(2,count($tree->getChildren()[0]->getChildren()));
    }

    /**
     * @expectedException \SAccounts\Storage\Exceptions\StorageException
     */
    public function testFetchingAChartWillThrowAnExceptionIfOrganisationDoesNotExist()
    {
        $this->sut->fetch(new StringType('Test'), new IntType(1));
    }

    /**
     * @expectedException \SAccounts\Storage\Exceptions\StorageException
     */
    public function testFetchingANonExistentChartWillThrowAnException()
    {
        $this->orgGW->create(
            new StringType('Foo'),
            Crcy::create('GBP'),
            new IntType(1)
        );
        $this->sut->fetch(new StringType('Test'), new IntType(1));
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

        //account type table
        $ddl = 'create table IF NOT EXISTS sa_ac_type (type TEXT primary key, value INTEGER)';
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_ac_type', DbAdapter::QUERY_MODE_EXECUTE);
        $sql = <<<EOF
INSERT INTO sa_ac_type (type, value) VALUES 
  ('ASSET', 11),
  ('BANK', 27),
  ('CR', 5),
  ('CUSTOMER', 44),
  ('DR', 3),
  ('DUMMY', 0),
  ('EQUITY', 645),
  ('EXPENSE', 77),
  ('INCOME', 389),
  ('LIABILITY', 133),
  ('REAL', 1),
  ('SUPPLIER', 1157);
EOF;
        $db->query($sql);

        //Available Currency table
        $ddl = 'create table IF NOT EXISTS sa_crcy (id INTEGER primary key,  code TEXT UNIQUE)';
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_crcy', DbAdapter::QUERY_MODE_EXECUTE);
        $db->query("INSERT INTO sa_crcy (code) VALUES ('GBP'), ('EUR'), ('USD')");

        //Organisation table
        $ddl = 'create table if not exists sa_org (id INTEGER PRIMARY KEY, name TEXT, crcyCode TEXT, rowSts TEXT DEFAULT \'active\')';
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_org', DbAdapter::QUERY_MODE_EXECUTE);

        //Chart of accounts table
        $ddl = <<<EOF
create table IF NOT EXISTS sa_coa
(
  id INTEGER primary key,
  orgId INTEGER,
  name TEXT,
  crcyCode TEXT,
  acDr INTEGER,
  acCr INTEGER,
  rowSts TEXT DEFAULT 'active'
)
EOF;

        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa', DbAdapter::QUERY_MODE_EXECUTE);

        //Chart of Accounts Ledger table
        $ddl =<<<EOF
create table if not exists sa_coa_ledger 
(
  id INTEGER primary key,
  chartId INTEGER NOT NULL ,
  nominal TEXT NOT NULL ,
  type TEXT NOT NULL ,
  name TEXT NOT NULL ,
  acDr INTEGER DEFAULT 0,
  acCr INTEGER DEFAULT 0,
  rowSts TEXT DEFAULT 'active'
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa_ledger', DbAdapter::QUERY_MODE_EXECUTE);

        //Chart of accounts link table
        $ddl = <<<EOF
create table if not exists sa_coa_link
(
  prnt INTEGER not null,
  child INTEGER not null,
  rowSts TEXT DEFAULT 'active',
  primary key (prnt, child)
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa_link', DbAdapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Remove sqlite db
     */
    public static function tearDownAfterClass()
    {
        self::$zendAdapter = null;
        unlink(__DIR__ . '/resources/sqlite.db');
    }

    protected function chartDefinition()
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<chart  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="chart-definition.xsd"
        name="Personal">
    <account id="1" nominal="0000" type="real" name="COA">
        <account id="2" nominal="1000" type="real" name="Balance Sheet">
           <account id="3" nominal="2000" type="asset" name="Assets">
                <account id="4" nominal="2100" type="bank" name="At Bank">
                    <account id="5" nominal="2110" type="bank" name="Current Account"/>
                    <account id="6" nominal="2120" type="bank" name="Savings Account"/>
                </account>
            </account>
            <account id="7" nominal="3000" type="liability" name="Liabilities">
                <account id="8" nominal="3100" type="equity" name="Equity">
                    <account id="9" nominal="3110" type="equity" name="Opening Balance"/>
                </account>
                <account id="10" nominal="3200" type="liability" name="Loans">
                    <account id="11" nominal="3210" type="liability" name="Mortgage"/>
                </account>
            </account>
        </account>
        <account id="12" nominal="5000" type="real" name="Profit And Loss">
            <account id="13" nominal="6000" type="income" name="Income">
                <account id="14" nominal="6100" type="income" name="Salary"/>
                <account id="15" nominal="6200" type="income" name="Interest Received"/>
            </account>
            <account id="16" nominal="7000" type="expense" name="Expenses">
                <account id="17" nominal="7100" type="expense" name="House"/>
                <account id="18" nominal="7200" type="expense" name="Travel"/>
                <account id="19" nominal="7300" type="expense" name="Insurance"/>
                <account id="20" nominal="7400" type="expense" name="Food"/>
                <account id="21" nominal="7500" type="expense" name="Leisure"/>
                <account id="22" nominal="7600" type="expense" name="Interest Payments"/>
            </account>
        </account>
    </account>
</chart>
EOF;
    }
}
