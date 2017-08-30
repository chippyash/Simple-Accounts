<?php
/**
 * SAccounts
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace Chippyash\Test\SAccounts\Storage\Account\ZendDBAccount;

use SAccounts\RecordStatus;

class RecordStatusTest extends \PHPUnit_Framework_TestCase
{
    public function testCanGetValuesAsConstants()
    {
        $this->assertInternalType('string', RecordStatus::ACTIVE);
        $this->assertEquals('active', RecordStatus::ACTIVE);
        $this->assertInternalType('string', RecordStatus::SUSPENDED);
        $this->assertEquals('suspended', RecordStatus::SUSPENDED);
        $this->assertInternalType('string', RecordStatus::DEFUNCT);
        $this->assertEquals('defunct', RecordStatus::DEFUNCT);
    }

    public function testCanGetValuesAsClassesUsingStaticMethods()
    {
        $this->assertInstanceOf(
            'SAccounts\RecordStatus',
            RecordStatus::ACTIVE()
        );
        $this->assertInstanceOf(
            'SAccounts\RecordStatus',
            RecordStatus::SUSPENDED()
        );
        $this->assertInstanceOf(
            'SAccounts\RecordStatus',
            RecordStatus::DEFUNCT()
        );
    }

    public function testYouCanTestChangingFromActiveToAnotherStatus()
    {
        $this->assertTrue(RecordStatus::ACTIVE()->canChange());
    }

    public function testYouCanTestChangingFromSuspendedToAnotherStatus()
    {
        $this->assertTrue(RecordStatus::SUSPENDED()->canChange());
    }

    public function testChangingFromDefunctStausToAnotherStatusIsNotAllowed()
    {
        $this->assertFalse(RecordStatus::DEFUNCT()->canChange());
    }
}
