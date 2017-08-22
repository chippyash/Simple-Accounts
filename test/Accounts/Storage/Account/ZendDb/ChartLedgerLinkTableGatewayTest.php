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
use SAccounts\Storage\Account\ZendDB\RecordStatus;
use Zend\Db\Adapter\Adapter as DbAdapter;
use SAccounts\Storage\Account\ZendDB\ChartLedgerLinkTableGateway;

class ChartLedgerLinkTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;

    /**
     * @var ChartLedgerLinkTableGateway
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
        $this->sut = new ChartLedgerLinkTableGateway(self::$zendAdapter);
    }

    protected function tearDown()
    {
        $this->sut->delete([]);
    }

    public function testYouCanAddALedgerLinkRecord()
    {
        $this->assertTrue($this->sut->create(
            new IntType(1),
            new IntType(2)
        ));

        $this->assertEquals(1, $this->sut->select(['prnt'=>1,'child'=>2])->count());
    }

    public function testAddingALedgerLinkRecordWillSetDefaultValuesForTheTableStatusFields()
    {
        $this->assertTrue($this->sut->create(
            new IntType(1),
            new IntType(2)
        ));

        $test = $this->sut->select(['prnt'=>1,'child'=>2]);
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

    public function testSettingTheRecordStatusWillReturnTrueIfSuccessful()
    {
        $this->assertTrue($this->sut->create(
            new IntType(1),
            new IntType(2)
        ));

        $this->assertTrue(
            $this->sut->setStatus(
                RecordStatus::SUSPENDED(),
                [
                    'prnt' => 1,
                    'child' => 2
                ]
            )
        );

        $this->assertEquals(
            'suspended',
            $this->sut->select(['prnt'=>1,'child'=>2])
                ->current()
                ->offsetGet('rowSts')
        );
    }

    public function testSettingTheRecordStatusWillReturnFalseIfNotSuccessful()
    {
        $this->assertFalse(
            $this->sut->setStatus(
                RecordStatus::SUSPENDED(),
                [
                    'prnt' => 1,
                    'child' => 2
                ]
            )
        );
    }

    public function testYouCanGetTheRecordStatus()
    {
        $this->assertTrue($this->sut->create(
            new IntType(1),
            new IntType(2)
        ));

        $this->sut->setStatus(
            RecordStatus::DEFUNCT(),
            [
                'prnt' => 1,
                'child' => 2
            ]
        );
        $test = $this->sut->getStatus(
            [
                'prnt' => 1,
                'child' => 2
            ]
        );
        $this->assertInstanceOf(
            '\SAccounts\Storage\Account\ZendDB\RecordStatus',
            $test
        );
        $this->assertEquals('defunct', $test->getValue());
    }

    /**
     * @expectedException \SAccounts\Storage\Exceptions\StorageException
     */
    public function testGettingTheStatusForAnUnknownRecordWillThrowAnException()
    {
        $this->sut->getStatus(
            [
                'prnt' => 1,
                'child' => 2
            ]
        );
    }
}
