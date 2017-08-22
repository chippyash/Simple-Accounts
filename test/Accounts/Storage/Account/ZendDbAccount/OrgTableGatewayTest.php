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
use SAccounts\Storage\Account\ZendDBAccount\OrgTableGateway;
use Chippyash\Currency\Factory as Crcy;

class OrgTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;
    /**
     * @var OrgTableGateway
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

        //Org table
        $ddl = <<<EOF
create table if NOT EXISTS sa_org (
  id INTEGER primary key,
  name TEXT not null,
  crcyCode TEXT default 'GBP' not null,
  rowDt DATETIME DEFAULT CURRENT_TIMESTAMP,
  rowSts TEXT DEFAULT 'active',
  rowUid INTEGER DEFAULT 0
)
EOF;
        $db->query($ddl, DbAdapter::QUERY_MODE_EXECUTE);
        $db->query('delete from sa_org', DbAdapter::QUERY_MODE_EXECUTE);
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
        $this->sut = new OrgTableGateway(self::$zendAdapter);
    }

    protected function tearDown()
    {
        $this->sut->delete([]);
    }

    public function testCreatingANewOrganisationRecordWillReturnTheInternalIdIfItIsNotProvided()
    {
        $id = $this->sut->create(
            new StringType('Test'),
            Crcy::create('GBP')
        );

        $this->assertEquals(1, $id);
    }

    public function testCreatingANewOrganisationRecordWillReturnTheGivenIdIfItIsProvided()
    {
        $id = $this->sut->create(
            new StringType('Test'),
            Crcy::create('GBP'),
            new IntType(1)
        );

        $this->assertEquals(1, $id);
    }

    public function testYouCanTestThatAnOrganisationExistsForAGivenOrgid()
    {
        $this->sut->create(
            new StringType('Test'),
            Crcy::create('GBP'),
            new IntType(1)
        );

        $this->assertTrue(
            $this->sut->has(
                new IntType(1)
            )
        );

        $this->assertFalse(
            $this->sut->has(
                new IntType(2)
            )
        );
    }

//    public function testCreatingANewChartRecordWillSetDefaultValuesForTheTableStatusFields()
//    {
//        $id = $this->sut->create(
//            new StringType('Test'),
//            new IntType(1),
//            Crcy::create('GBP')
//        );
//
//        $test = $this->sut->select(['id' => $id]);
//        $this->assertEquals(
//            'active',
//            $test->current()->offsetGet('rowSts')
//        );
//        $this->assertEquals(
//            0,
//            $test->current()->offsetGet('rowUid')
//        );
//        $dt = (new \Datetime($test->current()->offsetGet('rowDt')))->format('Y-M-d');
//        $now = (new \Datetime())->format('Y-M-d');
//        $this->assertEquals($now, $dt);
//    }
}
