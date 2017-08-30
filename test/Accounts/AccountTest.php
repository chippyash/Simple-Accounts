<?php
/**
 * SAccounts
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts;

use SAccounts\Account;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Nominal;
use SAccounts\Organisation;
use Chippyash\Currency\Factory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\RecordStatus;

class AccountTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Account
     */
    protected $sut;

    /**
     * @var Chart
     */
    protected $chart;

    protected function setUp()
    {
        $this->chart = new Chart(
            new StringType('foo'),
            new Organisation(
                new IntType(1),
                new StringType('foo'),
                Factory::create('gbp')
            )
        );
    }

    /**
     * @dataProvider validAccountTypes
     */
    public function testYouCanCreateAnyValidAccountType($acType)
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            $acType,
            new StringType('foo')
        );
        $this->assertInstanceOf('SAccounts\Account', $this->sut);
    }

    public function validAccountTypes()
    {
        return [
            [AccountType::DUMMY()],
            [AccountType::DR()],
            [AccountType::CR()],
            [AccountType::ASSET()],
            [AccountType::LIABILITY()],
            [AccountType::BANK()],
            [AccountType::CUSTOMER()],
            [AccountType::EQUITY()],
            [AccountType::EXPENSE()],
            [AccountType::INCOME()],
            [AccountType::REAL()],
            [AccountType::SUPPLIER()],
        ];
    }

    /**
     * @dataProvider validAccountTypes
     */
    public function testYouCanDebitAndCreditAnyAccountType($acType)
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            $acType,
            new StringType('foo')
        );
        $this->assertEquals(100, $this->sut->debit(Factory::create('gbp',1))->getDebit()->get());
        $this->assertEquals(100, $this->sut->credit(Factory::create('gbp',1))->getCredit()->get());
    }

    /**
     * @dataProvider accountTypesThatHaveBalance
     */
    public function testYouCanGetABalanceForAccountTypesThatSupportIt($acType)
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            $acType,
            new StringType('foo')
        );
        $this->assertInstanceOf('Chippyash\Currency\Currency', $this->sut->getBalance());
    }

    public function accountTypesThatHaveBalance()
    {
        return [
            [AccountType::DR()],
            [AccountType::CR()],
            [AccountType::ASSET()],
            [AccountType::LIABILITY()],
            [AccountType::BANK()],
            [AccountType::CUSTOMER()],
            [AccountType::EQUITY()],
            [AccountType::EXPENSE()],
            [AccountType::INCOME()],
            [AccountType::SUPPLIER()],
        ];
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testGettingBalanceOfARealAccountTypeWillThrowAnException()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::REAL(),
            new StringType('foo')
        );
        $this->sut->getBalance();
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testGettingBalanceOfADummyAccountTypeWillThrowAnException()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->sut->getBalance();
    }

    public function testDebitingAnAccountWillDebitItsParentIfOneExistsInAChart()
    {
        $ac1 = new Account(
            $this->chart,
            new Nominal('9998'),
            AccountType::DR(),
            new StringType('foo1')
        );
        $ac2 = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DR(),
            new StringType('foo2')
        );
        $this->chart->addAccount($ac1);
        $this->chart->addAccount($ac2, $ac1->getNominal());

        $ac2->debit(Factory::create('gbp',1));
        $this->assertEquals(100, $ac1->getDebit()->get());
    }

    public function testCreditingAnAccountWillCreditItsParentIfOneExistsInAChart()
    {
        $ac1 = new Account(
            $this->chart,
            new Nominal('9998'),
            AccountType::DR(),
            new StringType('foo1')
        );
        $ac2 = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DR(),
            new StringType('foo2')
        );
        $this->chart->addAccount($ac1);
        $this->chart->addAccount($ac2, $ac1->getNominal());

        $ac2->credit(Factory::create('gbp',1));
        $this->assertEquals(100, $ac1->getCredit()->get());
    }


    public function testYouCanGetTheAccountId()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertEquals(new Nominal('9999'), $this->sut->getNominal());
    }

    public function testYouCanGetTheAccountType()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertEquals(AccountType::DUMMY(), $this->sut->getType());
    }

    public function testYouCanGetTheAccountName()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertEquals(new StringType('foo'), $this->sut->getName());
    }

    public function testYouCanGetTheOrganisationThatTheAccountBelongsTo()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertEquals('foo', $this->sut->getOrg()->getName()->get());
    }

    public function testYouCanGetTheChartThatTheAccountBelongsTo()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertEquals($this->chart, $this->sut->getChart());
    }

    public function testYouCanGetTheRecordStatusForTheAccount()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertInstanceOf('SAccounts\RecordStatus', $this->sut->getStatus());
    }

    public function testTheDefaultStatusForANewAccountIsActive()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo')
        );
        $this->assertTrue(RecordStatus::ACTIVE()->equals($this->sut->getStatus()));
    }

    public function testYouCanOptionallyProvideARecordStatusOnConstruction()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            null,
            RecordStatus::SUSPENDED()
        );
        $this->assertTrue(RecordStatus::SUSPENDED()->equals($this->sut->getStatus()));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testTryingToChangeTheStatusOfADefunctAccountWillThrowAnException()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            null,
            RecordStatus::DEFUNCT()
        );
        $this->sut->setStatus(RecordStatus::ACTIVE());
    }

    public function testYouCanChangeTheStatusOfANonDefunctAccount()
    {
        $this->sut = new Account(
            $this->chart,
            new Nominal('9999'),
            AccountType::DUMMY(),
            new StringType('foo'),
            null,
            RecordStatus::SUSPENDED()
        );
        $this->sut->setStatus(RecordStatus::ACTIVE());
        $this->assertTrue(RecordStatus::ACTIVE()->equals($this->sut->getStatus()));
    }
}