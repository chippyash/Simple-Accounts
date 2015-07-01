<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace chippyash\Test\SAccounts\Control;

use chippyash\Type\String\StringType;
use SAccounts\Control\Link;
use SAccounts\Control\Links;
use SAccounts\Nominal;

class LinksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System Under Test
     * @var Links
     */
    protected $sut;
    
    protected function setUp()
    {
        $linkArray = [
            new Link(new StringType('foo'), new Nominal('1000')),
            new Link(new StringType('bar'), new Nominal('2000')),
            new Link(new StringType('baz'), new Nominal('3000')),
        ];
        $this->sut = (new Links($linkArray))->setName(new StringType('foo'));
    }

    public function testYouCanGetTheName()
    {
        $this->assertEquals('foo', $this->sut->getName()->get());
    }

    public function testYouCanSetTheName()
    {
        $this->assertEquals('bar', $this->sut->setName(new StringType('bar'))->getName()->get());
    }

    public function testYouCanAddAnotherLink()
    {
        $this->sut = $this->sut->addEntry(new Link(new StringType('fie'), new Nominal('4000')));
        $this->assertEquals(4, count($this->sut));
        $this->assertEquals('foo', $this->sut->getName()->get());
    }

    public function testYouCanRetrieveAControlLinkByName()
    {
        $this->assertInstanceOf('SAccounts\Control\Link', $this->sut->getLink(new StringType('foo')));
    }

    public function testRetrievingANonExistentControlLinkByNameWillReturnNull()
    {
        $this->assertNull($this->sut->getLink(new StringType('foofoo')));
    }

    public function testYouCanRetrieveAControlLinkIdByName()
    {
        $this->assertInstanceOf('SAccounts\Nominal', $this->sut->getLinkId(new StringType('foo')));
    }

    public function testRetrievingANonExistentControlLinkIdByNameWillReturnNull()
    {
        $this->assertNull($this->sut->getLinkId(new StringType('foofoo')));
    }
}
