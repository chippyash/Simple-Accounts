<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Ltd, 2017, UK
 * @license   Proprietary See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Account;


use chippyash\Chippyash\SAccounts\Storage\Account\ZendDb;
use Zend\Db\Adapter\Platform\Sqlite;

class ZendDbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var
     */
    protected static $zendAdapter;

    /**
     * System Under Test
     *
     * @var
     */
    protected $sut;

    public static function setUpBeforeClass()
    {
        self::$zendAdapter = new Sqlite();
    }

    public static function tearDownAfterClass()
    {
        self::$zendAdapter = null;
    }

    protected function setUp()
    {
        $this->sut = new ZendDb();
    }

    public function testCase()
    {

    }
}
