<?php
/**
 * Accounts
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace chippyash\Test\Accounts;

use chippyash\Accounts\Account;
use chippyash\Accounts\AccountType;
use chippyash\Accounts\Chart;
use chippyash\Accounts\Organisation;
use chippyash\Accounts\Nominal;
use chippyash\Currency\Factory;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use Tree\Node\Node;

class ChartTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Chart
     */
    protected $sut;

    /**
     * @var Organisation
     */
    protected $org;

    /**
     * @var Node
     */
    protected $tree;

    protected function setUp()
    {
        $this->org = new Organisation(new IntType(1), new StringType('Foo'), Factory::create('gbp'));
        $this->sut = new Chart(new StringType('Foo Chart'), $this->org);
    }

    public function testConstructionCreatesChart()
    {
        $this->assertInstanceOf('chippyash\Accounts\Chart', $this->sut);
    }

    public function testYouCanGiveAChartAnOptionalTreeInConstruction()
    {
        $tree = new Node();
        $sut = new Chart(new StringType('Foo Chart'), $this->org, $tree);
        $this->assertInstanceOf('chippyash\Accounts\Chart', $sut);
    }

    public function testYouCanAddAnAccountIfItIsNotAlreadyInTheChart()
    {
        $ac = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset'));
        $this->assertInstanceOf('chippyash\Accounts\Chart', $this->sut->addAccount($ac));
    }

    /**
     * @expectedException chippyash\Accounts\AccountsException
     */
    public function testAddingAnAccountThatAlreadyExistsInChartWillThrowException()
    {
        $ac = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac);
        $this->sut->addAccount($ac);
    }

    public function testYouCanAddAnAccountWithAParent()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac2 = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset-2'));
        $this->sut->addAccount($ac2,$ac1->getId());

        $rootChildren = $this->sut->getTree()->getChildren();
        $testAc1 = $rootChildren[0]->getValue();
        $this->assertEquals($ac1, $testAc1);
        $ac1Children = $rootChildren[0]->getChildren();
        $testAc2 = $ac1Children[0]->getValue();
        $this->assertEquals($ac2, $testAc2);
    }

    public function testYouCanGetAnAccountIfItExists()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac2 = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset-2'));
        $this->sut->addAccount($ac2,$ac1->getId());

        $testAc2 = $this->sut->getAccount($ac2->getId());
        $this->assertEquals($ac2, $testAc2);
    }

    /**
     * @expectedException chippyash\Accounts\AccountsException
     */
    public function testTryingToGetANonExistentAccountWillThrowAnException()
    {
        $this->sut->getAccount(new Nominal('9999'));
    }

    /**
     * @expectedException chippyash\Accounts\AccountsException
     */
    public function testDeletingANonExistentAccountWillThrowAnException()
    {
        $this->sut->delAccount(new Nominal('9999'));
    }

    /**
     * @expectedException \chippyash\Accounts\AccountsException
     */
    public function testYouCannotDeleteAnAccountIfItsBalanceIsNonZero()
    {
        $ac1 = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac1->debit(Factory::create($this->sut->getOrg()->getCurrencyCode(), 12.26));
        $this->sut->delAccount($ac1->getId());
    }

    public function testYouCanDeleteAnAccountIfItsBalanceIsZero()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac1->debit(Factory::create($this->sut->getOrg()->getCurrencyCode()));
        $this->sut->delAccount($ac1->getId());
        $this->assertFalse($this->sut->hasAccount($ac1->getId()));

        $ac2 = new Account($this->sut, new Nominal('9999'), AccountType::LIABILITY(), new StringType('Asset'));
        $this->sut->addAccount($ac2);
        $ac1->credit(Factory::create($this->sut->getOrg()->getCurrencyCode()));
        $this->sut->delAccount($ac2->getId());
        $this->assertFalse($this->sut->hasAccount($ac2->getId()));
    }

    public function testYouCanTestIfAChartHasAnAccount()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $this->assertTrue($this->sut->hasAccount($ac1->getId()));
        $this->assertFalse($this->sut->hasAccount(new Nominal('9999')));
    }

    /**
     * @expectedException \chippyash\Accounts\AccountsException
     */
    public function testTryingToGetAParentIdOfANonExistentAccountWillThrowAnException()
    {
        $this->sut->getParentId(new Nominal('9999'));
    }

    public function testGettingTheParentIdOfAnAccountThatHasAParentWillReturnTheParentId()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac2 = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset-2'));
        $this->sut->addAccount($ac2,$ac1->getId());

        $f = $this->sut->getParentId($ac2->getId());
        $b = $f->get();
        $this->assertEquals('9998', $this->sut->getParentId($ac2->getId())->get());
    }
}
