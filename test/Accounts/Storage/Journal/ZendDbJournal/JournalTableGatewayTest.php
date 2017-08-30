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

}
