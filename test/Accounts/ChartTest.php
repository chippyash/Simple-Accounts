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
use SAccounts\Organisation;
use SAccounts\Nominal;
use Chippyash\Currency\Factory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
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
        $this->assertInstanceOf('SAccounts\Chart', $this->sut);
    }

    public function testYouCanGiveAChartAnOptionalTreeInConstruction()
    {
        $tree = new Node();
        $sut = new Chart(new StringType('Foo Chart'), $this->org, $tree);
        $this->assertInstanceOf('SAccounts\Chart', $sut);
    }

    public function testYouCanAddAnAccountIfItIsNotAlreadyInTheChart()
    {
        $ac = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset'));
        $this->assertInstanceOf('SAccounts\Chart', $this->sut->addAccount($ac));
    }

    /**
     * @expectedException \SAccounts\AccountsException
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
        $this->sut->addAccount($ac2,$ac1->getNominal());

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
        $this->sut->addAccount($ac2,$ac1->getNominal());

        $testAc2 = $this->sut->getAccount($ac2->getNominal());
        $this->assertEquals($ac2, $testAc2);
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testTryingToGetANonExistentAccountWillThrowAnException()
    {
        $this->sut->getAccount(new Nominal('9999'));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testDeletingANonExistentAccountWillThrowAnException()
    {
        $this->sut->delAccount(new Nominal('9999'));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testYouCannotDeleteAnAccountIfItsBalanceIsNonZero()
    {
        $ac1 = new Account($this->sut, new Nominal('9999'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac1->debit(Factory::create($this->sut->getOrg()->getCurrencyCode(), 12.26));
        $this->sut->delAccount($ac1->getNominal());
    }

    public function testYouCanDeleteAnAccountIfItsBalanceIsZero()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $ac1->debit(Factory::create($this->sut->getOrg()->getCurrencyCode()));
        $this->sut->delAccount($ac1->getNominal());
        $this->assertFalse($this->sut->hasAccount($ac1->getNominal()));

        $ac2 = new Account($this->sut, new Nominal('9999'), AccountType::LIABILITY(), new StringType('Asset'));
        $this->sut->addAccount($ac2);
        $ac1->credit(Factory::create($this->sut->getOrg()->getCurrencyCode()));
        $this->sut->delAccount($ac2->getNominal());
        $this->assertFalse($this->sut->hasAccount($ac2->getNominal()));
    }

    public function testYouCanTestIfAChartHasAnAccount()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $this->sut->addAccount($ac1);
        $this->assertTrue($this->sut->hasAccount($ac1->getNominal()));
        $this->assertFalse($this->sut->hasAccount(new Nominal('9999')));
    }

    /**
     * @expectedException \SAccounts\AccountsException
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
        $this->sut->addAccount($ac2,$ac1->getNominal());

        $this->assertEquals('9998', $this->sut->getParentId($ac2->getNominal())->get());
    }

    public function testYouCanProvideAnOptionalInternalIdWhenConstructingAChart()
    {
        $sut = new Chart(
            new StringType('Foo'),
            $this->org,
            null,
            new IntType(12)
        );

        $this->assertEquals(12, $sut->id()->get());
    }

    public function testYouCanSetTheChartRootNode()
    {
        $ac1 = new Account($this->sut, new Nominal('9998'), AccountType::ASSET(), new StringType('Asset'));
        $root = new Node($ac1);
        $this->sut->setRootNode($root);
        $tree = $this->sut->getTree();

        $this->assertEquals($root, $tree);
    }
}
