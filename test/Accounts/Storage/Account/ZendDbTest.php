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
use SAccounts\Accountant;
use SAccounts\Chart;
use SAccounts\ChartDefinition;
use SAccounts\Organisation;
use SAccounts\Storage\Account\ZendDbAccount;
use SAccounts\Storage\Account\ZendDB\ChartTableGateway;
use SAccounts\Storage\Account\ZendDB\OrgTableGateway;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Chippyash\Currency\Factory as Crcy;

class ZendDbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    protected static $zendAdapter;

    /**
     * System Under Test
     *
     * @var
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
        $ddl = 'create table if not exists sa_org (id INTEGER PRIMARY KEY ASC, name TEXT, crcyCode TEXT)';
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_org', DbAdapter::QUERY_MODE_EXECUTE);
        $db->query("insert into sa_org (id, name) values (1, 'Test')");

        //Chart of accounts table
        $ddl = <<<EOF
create table IF NOT EXISTS sa_coa
(
  id INTEGER primary key,
  orgId INTEGER,
  nominal TEXT,
  type TEXT,
  name TEXT,
  crcyCode TEXT,
  acDr INTEGER,
  acCr INTEGER
)
EOF;

        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa', DbAdapter::QUERY_MODE_EXECUTE);

        //Chart of Accounts Ledger table
        $ddl = 'create table if not exists sa_coa_ledger (chartId INTEGER, name TEXT, orgId INTEGER, PRIMARY KEY (name, orgId))';
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);

    }

    /**
     * Remove sqlite db
     */
    public static function tearDownAfterClass()
    {
        self::$zendAdapter = null;
        unlink(__DIR__ . '/resources/sqlite.db');
    }

    protected function setUp()
    {
        $this->sut = new ZendDbAccount(
            new OrgTableGateway(self::$zendAdapter),
            new ChartTableGateway(self::$zendAdapter)
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

    public function testYouCanSendAChart()
    {
       // $this->sut->send($this->chart);
       // $this->assertEquals($test, $chart);
    }

    protected function chartDefinition()
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<chart  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="chart-definition.xsd"
        name="Personal">
    <account nominal="0000" type="real" name="COA">
        <account nominal="1000" type="real" name="Balance Sheet">
            <account nominal="2000" type="asset" name="Assets">
                <account nominal="2100" type="bank" name="At Bank">
                    <account nominal="2110" type="bank" name="Current Account"/>
                    <account nominal="2120" type="bank" name="Savings Account"/>
                </account>
                <account nominal="3000" type="liability" name="Liabilities">
                    <account nominal="3100" type="equity" name="Equity">
                        <account nominal="3110" type="equity" name="Opening Balance"/>
                    </account>
                    <account nominal="3200" type="liability" name="Loans">
                        <account nominal="3210" type="liability" name="Mortgage"/>
                    </account>
                </account>
            </account>
        </account>
        <account nominal="5000" type="real" name="Profit And Loss">
            <account nominal="6000" type="income" name="Income">
                <account nominal="6100" type="income" name="Salary"/>
                <account nominal="6200" type="income" name="Interest Received"/>
            </account>
            <account nominal="7000" type="expense" name="Expenses">
                <account nominal="7100" type="expense" name="House"/>
                <account nominal="7200" type="expense" name="Travel"/>
                <account nominal="7300" type="expense" name="Insurance"/>
                <account nominal="7400" type="expense" name="Food"/>
                <account nominal="7500" type="expense" name="Leisure"/>
                <account nominal="7600" type="expense" name="Interest Payments"/>
            </account>
        </account>
    </account>
</chart>
EOF;
    }
}
