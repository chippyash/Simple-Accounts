<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts\Transaction;

use Chippyash\Currency\Factory;
use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;

class EntryTest extends \PHPUnit_Framework_TestCase
{
    public function testAnEntryRequiresAnIdAnAmountAndAType()
    {
        $sut = new Entry(new Nominal('9999'), Factory::create('gbp'), AccountType::CR());
        $this->assertInstanceOf('SAccounts\Transaction\Entry', $sut);
    }

    public function testAnEntryMustHaveCrOrDrType()
    {
        $sut = new Entry(new Nominal('9999'), Factory::create('gbp'), AccountType::CR());
        $this->assertInstanceOf('SAccounts\Transaction\Entry', $sut);
        $sut = new Entry(new Nominal('9999'), Factory::create('gbp'), AccountType::DR());
        $this->assertInstanceOf('SAccounts\Transaction\Entry', $sut);
    }

    /**
     * @dataProvider invalidAccountTypes
     * @expectedException \SAccounts\AccountsException
     * @param AccountType $type
     */
    public function testConstructingAnEntryWithInvalidTypeWillThrowException($type)
    {
        $sut = new Entry(new Nominal('9999'), Factory::create('gbp'), $type);
    }

    public function invalidAccountTypes()
    {
        return array(
            array(AccountType::ASSET()),
            array(AccountType::BANK()),
            array(AccountType::CUSTOMER()),
            array(AccountType::EQUITY()),
            array(AccountType::EXPENSE()),
            array(AccountType::INCOME()),
            array(AccountType::LIABILITY()),
            array(AccountType::REAL()),
            array(AccountType::SUPPLIER()),
        );
    }

    public function testYouCanGetTheIdOfAnEntry()
    {
        $this->assertEquals(
            '9999',
            (new Entry(new Nominal('9999'), Factory::create('gbp'), AccountType::CR()))
                ->getId()
                ->get()
        );
    }

    public function testYouCanGetTheAmountOfAnEntry()
    {
        $this->assertEquals(
            100,
            (new Entry(new Nominal('9999'), Factory::create('gbp', 1), AccountType::CR()))
                ->getAmount()
                ->get()
        );
    }

    public function testYouCanGetTheTypeOfAnEntry()
    {
        $this->assertEquals(
            AccountType::CR(),
            (new Entry(new Nominal('9999'), Factory::create('gbp', 1), AccountType::CR()))
                ->getType()
        );
    }
}
