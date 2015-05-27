<?php
/**
 * Accounts
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Test\Accounts\Storage\Account;

use chippyash\Accounts\Chart;
use chippyash\Accounts\Organisation;
use chippyash\Accounts\Storage\Account\Serialized;
use chippyash\Currency\Factory;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use org\bovigo\vfs\vfsStream;

class SerializedTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Serialized
     */
    protected $sut;

    /**
     * @var vfsStreamFile
     */
    protected $root;

    protected function setUp()
    {
        $this->root = vfsStream::setup();
        $this->sut = new Serialized(new StringType($this->root->url()));
    }

    /**
     * @expectedException chippyash\Accounts\AccountsException
     */
    public function testConstructionWithNonExistentDirectoryThrowsAnException()
    {
        $this->sut = new Serialized(new StringType($this->root->url() . '/foo/bar'));
    }

    public function testYouCanSendAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp')));
        $this->sut->send($chart);
        $contents = file_get_contents($this->root->url() . '/foo_bar.saccount');
        $test = unserialize($contents);
        $this->assertEquals($test, $chart);
    }

    /**
     * @expectedException \chippyash\Accounts\AccountsException
     */
    public function testFetchingANonExistentChartWillThrowAnException()
    {
        $this->sut->fetch(new StringType('foo bar'));
    }

    public function testYouCanFetchAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp')));
        file_put_contents($this->root->url() . '/foo_bar.saccount', serialize($chart));
        $this->assertEquals($chart, $this->sut->fetch(new StringType('foo bar')));
    }

}
