<?php
/**
 * SAccounts
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts;

use SAccounts\Organisation;
use Chippyash\Currency\Factory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;

class OrganisationTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Organisation
     */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new Organisation(new IntType(1), new StringType('foo'), Factory::create('gbp'));
    }

    public function testYouCanGetOrganisationId()
    {
        $this->assertEquals(1, $this->sut->id()->get());
    }

    public function testYouCanGetOrganisationName()
    {
        $this->assertEquals('foo', $this->sut->getName()->get());
    }

    public function testYouCanGetOrganisationCurrency()
    {
        $this->assertEquals(Factory::create('gbp'), $this->sut->getCurrency());
    }

    public function testYouCanGetOrganisationCurrencyCode()
    {
        $this->assertEquals('GBP', $this->sut->getCurrencyCode()->get());
    }


}
