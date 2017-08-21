<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Account\ZendDB;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Storage\Account\ZendDB\RecordStatus;
use Zend\Db\Adapter\Adapter as DbAdapter;
use SAccounts\Storage\Account\ZendDB\ChartLedgerLinkTableGateway;
use SAccounts\Storage\Account\ZendDB\ChartLedgerTableGateway;

class ChartLedgerTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;
    /**
     * @var ChartLedgerLinkTableGateway
     */
    protected $linkGw;
    /**
     * @var ChartLedgerTableGateway
     */
    protected $sut;

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
                'database' => __DIR__ . '/../resources/sqlite.db'
            ]
        );

        //COA Ledger table
        $ddl = <<<EOF
create table IF NOT EXISTS sa_coa_ledger ( 
  id INTEGER primary key,
  chartId INTEGER NOT NULL ,
  nominal TEXT NOT NULL ,
  type TEXT NOT NULL ,
  name TEXT NOT NULL ,
  acDr INTEGER DEFAULT 0 NOT NULL ,
  acCr INTEGER default 0 NOT NULL ,
  rowDt DATETIME DEFAULT CURRENT_TIMESTAMP,
  rowSts TEXT DEFAULT 'active',
  rowUid INTEGER DEFAULT 0
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_coa_ledger', DbAdapter::QUERY_MODE_EXECUTE);

        //COA Ledger Link table
        $ddl =<<<EOF
create table IF NOT EXISTS sa_coa_link ( 
  prnt INTEGER, 
  child INTEGER,
  rowDt DATETIME DEFAULT CURRENT_TIMESTAMP,
  rowSts TEXT DEFAULT 'active',
  rowUid INTEGER DEFAULT 0,
   PRIMARY KEY (prnt, child)
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
        unlink(__DIR__ . '/../resources/sqlite.db');
    }

    protected function setUp()
    {
        $this->linkGw = new ChartLedgerLinkTableGateway(self::$zendAdapter);
        $this->sut = new ChartLedgerTableGateway(self::$zendAdapter);
    }

    protected function tearDown()
    {
        $this->sut->delete([]);
        $this->linkGw->delete([]);
    }

    public function testYouCanTestIfALedgerRecordExistsForAChartIdAndNominalCode()
    {
        $this->assertFalse($this->sut->has(
            new IntType(1),
            new Nominal('000000')
        ));

        $this->sut->insert(
            [
                'chartId' => 1,
                'nominal' => '000000',
                'type' => 1,
                'name' => 'foo'
            ]
        );

        $this->assertTrue($this->sut->has(
            new IntType(1),
            new Nominal('000000')
        ));
    }

    public function testCreatingANewLedgerRecordWillReturnTheInternalId()
    {
        $id = $this->sut->createLedger(
            new IntType(1),
            new Nominal('000000'),
            AccountType::REAL(),
            new StringType('COA')
        );

        $this->assertEquals(1, $id);
    }

    public function testCreatingANewLedgerRecordWillSetDefaultValuesForTheTableStatusFields()
    {
        $id = $this->sut->createLedger(
            new IntType(1),
            new Nominal('000000'),
            AccountType::REAL(),
            new StringType('COA')
        );

        $test = $this->sut->select(['id' => $id]);
        $this->assertEquals(
            'active',
            $test->current()->offsetGet('rowSts')
        );
        $this->assertEquals(
            0,
            $test->current()->offsetGet('rowUid')
        );
        $dt = (new \Datetime($test->current()->offsetGet('rowDt')))->format('Y-M-d');
        $now = (new \Datetime())->format('Y-M-d');
        $this->assertEquals($now, $dt);
    }

    public function testCreatingANewLedgerRecordWillNotCreateALedgerLinkIfNoParentIsGiven()
    {
        $id = $this->sut->createLedger(
            new IntType(1),
            new Nominal('000000'),
            AccountType::REAL(),
            new StringType('COA')
        );

        $this->assertEquals(0, $this->linkGw->select(['prnt' => $id])->count());
    }

    public function testCreatingANewLedgerRecordWillCreateALedgerLinkIfAParentIsGiven()
    {
        $prnt = $this->sut->createLedger(
            new IntType(1),
            new Nominal('000000'),
            AccountType::REAL(),
            new StringType('COA')
        );

        $this->sut->createLedger(
            new IntType(1),
            new Nominal('100000'),
            AccountType::REAL(),
            new StringType('COA Sub ledger'),
            new IntType($prnt)
        );

        $this->assertEquals(1, $this->linkGw->select(['prnt' => $prnt])->count());
    }

    public function testCreatingANewLedgerRecordWillNotCreateALedgerLinkIfAParentIsGivenButParentDoesNotExist()
    {
        $this->sut->createLedger(
            new IntType(1),
            new Nominal('000000'),
            AccountType::REAL(),
            new StringType('COA'),
            new IntType(0)
        );

        $this->assertEquals(0, $this->linkGw->select(['prnt' => 0])->count());
    }

}
