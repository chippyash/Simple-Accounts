<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Journal\ZendDbJournal;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Zend\Db\Adapter\Adapter as DbAdapter;
use SAccounts\Storage\Journal\ZendDbJournal\JournalTableGateway;
use Chippyash\Currency\Factory as Crcy;

class JournalTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;
    /**
     * @var JournalTableGateway
     */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new JournalTableGateway(self::$zendAdapter);
    }

    protected function tearDown()
    {
        $this->sut->delete([]);
    }

    public function testCreatingANewJournalRecordWillReturnTheInternalId()
    {
        $id = $this->sut->create(
            new IntType(1)
        );

        $this->assertEquals(1, $id);
    }

    public function testYouCanSpecifyAnOptionalNoteWhenCreatingAJournal()
    {
        $id = $this->sut->create(
            new IntType(1),
            new StringType('foo')
        );

        $this->assertEquals(
            'foo',
            $this->sut->select(['id'=>$id])->current()->offsetGet('note')
        );
    }

    public function testYouCanOptionallySpecifyTheDateWhenCreatingAJournal()
    {
        $dt = new \DateTime('2017-08-21 12:00:00');
        $id = $this->sut->create(
            new IntType(1),
            new StringType('foo'),
            $dt
        );

        $this->assertEquals(
            '2017-08-21 12:00:00',
            $this->sut->select(['id'=>$id])->current()->offsetGet('date')
        );
    }

    public function testYouCanTestThatAJournalExistsForAGivenJournalId()
    {
        $id = $this->sut->create(
            new IntType(1)
        );
        $this->assertEquals(1, $id);

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
    }

    /**
     * Remove sqlite db
     */
    public static function tearDownAfterClass()
    {
        self::$zendAdapter = null;
        unlink(__DIR__ . '/../resources/sqlite.db');
    }
}
