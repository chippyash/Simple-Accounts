<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts\Storage\Account;

use SAccounts\Chart;
use SAccounts\Organisation;
use SAccounts\Storage\Account\Serialized;
use Chippyash\Currency\Factory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
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
     * @expectedException SAccounts\AccountsException
     */
    public function testConstructionWithNonExistentDirectoryThrowsAnException()
    {
        $this->sut = new Serialized(new StringType($this->root->url() . '/foo/bar'));
    }

    public function testYouCanSendAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp')));
        $this->sut->send($chart);
        $contents = file_get_contents($this->root->url() . '/foo_bar_1.saccount');
        $test = unserialize($contents);
        $this->assertEquals($test, $chart);
    }

    /**
     * @expectedException  \SAccounts\AccountsException
     */
    public function testFetchingANonExistentChartWillThrowAnException()
    {
        $this->sut->fetch(new StringType('foo bar'), new IntType(0));
    }

    public function testYouCanFetchAChart()
    {
        $chart = new Chart(
            new StringType('foo bar'),
            new Organisation(
                new IntType(1),
                new StringType('Foo Org'),
                Factory::create('gbp')
            )
        );
        file_put_contents($this->root->url() . '/foo_bar_1.saccount', serialize($chart));
        $this->assertEquals($chart, $this->sut->fetch(new StringType('foo bar'), new IntType(1)));
    }

}
