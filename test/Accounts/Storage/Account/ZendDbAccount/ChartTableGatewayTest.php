<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Account\ZendDBAccount;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Zend\Db\Adapter\Adapter as DbAdapter;
use SAccounts\Storage\Account\ZendDBAccount\ChartTableGateway;
use Chippyash\Currency\Factory as Crcy;

class ChartTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;
    /**
     * @var ChartTableGateway
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
        $this->sut = new ChartTableGateway(self::$zendAdapter);
    }

    protected function tearDown()
    {
        $this->sut->delete([]);
    }

    public function testCreatingANewChartRecordWillReturnTheInternalId()
    {
        $id = $this->sut->create(
            new StringType('Test'),
            new IntType(1),
            Crcy::create('GBP')
        );

        $this->assertEquals(1, $id);
    }

    public function testYouCanTestThatAChartExistsForAGivenOrgidAndChartName()
    {
        $this->sut->create(
            new StringType('Test'),
            new IntType(1),
            Crcy::create('GBP')
        );

        $this->assertTrue(
            $this->sut->has(
                new StringType('Test'),
                new IntType(1)
            )
        );

        $this->assertFalse(
            $this->sut->has(
                new StringType('Foo'),
                new IntType(1)
            )
        );

        $this->assertFalse(
            $this->sut->has(
                new StringType('Test'),
                new IntType(2)
            )
        );
    }

    public function testCreatingANewChartRecordWillSetDefaultValuesForTheTableStatusFields()
    {
        $id = $this->sut->create(
            new StringType('Test'),
            new IntType(1),
            Crcy::create('GBP')
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
}
