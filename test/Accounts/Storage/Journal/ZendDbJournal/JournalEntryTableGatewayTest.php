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
use SAccounts\Nominal;
use Zend\Db\Adapter\Adapter as DbAdapter;
use SAccounts\Storage\Journal\ZendDbJournal\JournalEntryTableGateway;
use Chippyash\Currency\Factory as Crcy;

class JournalEntryTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbAdapter
     */
    static protected $zendAdapter;
    /**
     * @var JournalEntryTableGateway
     */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new JournalEntryTableGateway(self::$zendAdapter);
    }

    protected function tearDown()
    {
        $this->sut->delete([]);
    }

    public function testCreatingANewJournalEntryRecordWillReturnTheInternalId()
    {
        $id = $this->sut->create(
            new IntType(2),
            new Nominal('0000'),
            Crcy::create('GBP', 0),
            Crcy::create('GBP', 100)
        );

        $this->assertEquals(1, $id);
    }

    public function testYouCanTestThatAJournalEntryExistsForAGivenId()
    {
        $id = $this->sut->create(
            new IntType(1),
            new Nominal('0000'),
            Crcy::create('GBP', 0),
            Crcy::create('GBP', 100)
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
