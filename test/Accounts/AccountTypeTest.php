<?php
/**
 * SAccounts
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Test\SAccounts;


use SAccounts\AccountType;
use SAccounts\AccountsException;
use chippyash\Currency\Currency;
use chippyash\Currency\Factory;

class AccountTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testCanGetValuesAsConstants()
    {
        $this->assertInternalType('numeric', AccountType::REAL);
        $this->assertInternalType('numeric', AccountType::DUMMY);
        $this->assertInternalType('numeric', AccountType::CR);
        $this->assertInternalType('numeric', AccountType::DR);
        $this->assertInternalType('numeric', AccountType::ASSET);
        $this->assertInternalType('numeric', AccountType::BANK);
        $this->assertInternalType('numeric', AccountType::CUSTOMER);
        $this->assertInternalType('numeric', AccountType::EXPENSE);
        $this->assertInternalType('numeric', AccountType::INCOME);
        $this->assertInternalType('numeric', AccountType::LIABILITY);
        $this->assertInternalType('numeric', AccountType::EQUITY);
        $this->assertInternalType('numeric', AccountType::SUPPLIER);
    }

    public function testCanGetValuesAsClassesUsingStaticMethods()
    {
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::REAL());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::CR());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::DR());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::ASSET());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::BANK());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::CUSTOMER());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::EXPENSE());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::INCOME());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::LIABILITY());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::EQUITY());
        $this->assertInstanceOf('SAccounts\AccountType', AccountType::SUPPLIER());
    }

    /**
     * @dataProvider validTitledata
     */
    public function testCanGetADebitColumnTitleForAValidAccountType($acType, $titles)
    {
        $ac = new AccountType($acType);
        $this->assertEquals($titles['dr'], $ac->drTitle());
    }

    /**
     * @expectedException SAccounts\AccountsException
     */
    public function testGetADebitColumnWithInvalidAccountTypeWillThrowException()
    {
        $ac = new AccountType(0);
        $ac->drTitle();
    }

    /**
     * @expectedException SAccounts\AccountsException
     */
    public function testGetACreditColumnWithInvalidAccountTypeWillThrowException()
    {
        $ac = new AccountType(0);
        $ac->crTitle();
    }

    /**
     * @dataProvider validTitledata
     */
    public function testCanGetACreditColumnTitleForAValidAccountType($acType, $titles)
    {
        $ac = new AccountType($acType);
        $this->assertEquals($titles['cr'], $ac->crTitle());
    }

    public function validTitleData()
    {
        return [
            [AccountType::DR, ['dr'=>'Debit','cr'=>'Credit']],
            [AccountType::CR, ['dr'=>'Debit','cr'=>'Credit']],
            [AccountType::ASSET, ['dr'=>'Increase','cr'=>'Decrease']],
            [AccountType::BANK, ['dr'=>'Increase','cr'=>'Decrease']],
            [AccountType::CUSTOMER, ['dr'=>'Increase','cr'=>'Decrease']],
            [AccountType::EXPENSE, ['dr'=>'Expense','cr'=>'Refund']],
            [AccountType::INCOME, ['dr'=>'Charge','cr'=>'Income']],
            [AccountType::LIABILITY, ['dr'=>'Decrease','cr'=>'Increase']],
            [AccountType::EQUITY, ['dr'=>'Decrease','cr'=>'Increase']],
            [AccountType::SUPPLIER, ['dr'=>'Decrease','cr'=>'Increase']],
        ];
    }

    /**
     * @dataProvider balanceData
     */
    public function testWillGetCorrectBalanceForAllValidAccountTypes($acType, $dr, $cr, $result)
    {
        $ac = new AccountType($acType);
        $test = $ac->balance(Factory::create('gbp',$dr), Factory::create('gbp',$cr));
        $this->assertEquals(Factory::create('gbp',$result)->get(), $test());
    }

    public function balanceData()
    {
        return [
            [AccountType::DR, 2, 1, 1],
            [AccountType::CR, 1, 2, 1],
            [AccountType::ASSET, 2, 1, 1],
            [AccountType::BANK, 2, 1, 1],
            [AccountType::CUSTOMER, 2, 1, 1],
            [AccountType::EXPENSE, 2, 1, 1],
            [AccountType::INCOME, 1, 2, 1],
            [AccountType::LIABILITY, 1, 2, 1],
            [AccountType::EQUITY, 1, 2, 1],
            [AccountType::SUPPLIER, 1, 2, 1],
        ];
    }

    /**
     * @expectedException SAccounts\AccountsException
     */
    public function testGetABalanceWithInvalidAccountTypeWillThrowException()
    {
        $ac = new AccountType(AccountType::DUMMY);
        $ac->balance(Factory::create('gbp'), Factory::create('gbp'));
    }


}
