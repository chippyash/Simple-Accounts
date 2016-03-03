<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Control;

use Chippyash\Type\String\StringType;
use SAccounts\Control\Link;
use SAccounts\Nominal;

class LinkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System Under Test
     * @var Link
     */
    protected $sut;
    
    protected function setUp()
    {
        $this->sut = new Link(new StringType('foo'), new Nominal('1000'));
    }

    public function testYouCanGetTheName()
    {
        $this->assertEquals('foo', $this->sut->getName()->get());
    }

    public function testYouCanGetTheIdOfTheAssociatedAccount()
    {
        $this->assertEquals('1000', $this->sut->getId()->get());
    }
}
