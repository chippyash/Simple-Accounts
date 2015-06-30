<?php
/**
 * Accounts
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Test\SAccounts\Transaction;

use chippyash\Currency\Factory;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SplitTransaction;

class SplitTransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SplitTransaction
     */
    protected $sut;

    protected function setUp()
    {
        $amount = Factory::create('gbp', 12.26);
        $this->sut = (new SplitTransaction())
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
    }

    public function testBasicConstructionSetsAnEmptyNoteOnTheTransaction()
    {
        $this->assertEquals('', $this->sut->getNote()->get());
    }

    public function testBasicConstructionSetsDateForTodayOnTheTransaction()
    {
        $dt = new \DateTime();
        $date = $dt->format('yyyy-mm-dd');
        $txnDate = $this->sut->getDate()->format('yyyy-mm-dd');
        $this->assertEquals($date, $txnDate);
    }

    public function testYouCanSetAnOptionalNoteOnConstruction()
    {
        $note = new StringType('foo bar');
        $amount = Factory::create('gbp', 12.26);
        $sut = (new SplitTransaction(null, $note))
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals($note, $sut->getNote());
    }

    public function testYouCanSetAnOptionalDateOnConstruction()
    {
        $note = new StringType('foo bar');
        $dt = new \DateTime();
        $amount = Factory::create('gbp', 12.26);
        $sut = (new SplitTransaction($dt, $note))
            ->addEntry(new Entry(new Nominal('0000'), $amount, AccountType::DR()))
            ->addEntry(new Entry(new Nominal('1000'), $amount, AccountType::CR()));
        $this->assertEquals($dt, $sut->getDate());
    }

    public function testConstructingASplitTransactionDoesNotSetItsId()
    {
        $this->assertNull($this->sut->getId());
    }

    public function testYouCanSetAndGetAnId()
    {
        $id = new IntType(1);
        $this->assertEquals($id, $this->sut->setId($id)->getId());
    }

    public function testGettingTheDebitAccountForASplitTransactionWillReturnAnArrayOfNominals()
    {
        $codes = $this->sut->getDrAc();
        $this->assertInternalType('array', $codes);
        $this->assertInstanceOf('SAccounts\Nominal', $codes[0]);
    }

    public function testGettingTheCreditAccountForASplitTransactionWillReturnAnArrayOfNominals()
    {
        $codes = $this->sut->getCrAc();
        $this->assertInternalType('array', $codes);
        $this->assertInstanceOf('SAccounts\Nominal', $codes[0]);
    }

    public function testCheckingIfASplitTransactionIsBalancedWillReturnTrueIfBalanced()
    {
        $this->assertTrue($this->sut->checkBalance());
    }

    public function testCheckingIfASplitTransactionIsBalancedWillReturnFalseIfNotBalanced()
    {
        $amount = Factory::create('gbp', 0.1);
        $this->sut->addEntry(new Entry(new Nominal('2000'), $amount, AccountType::CR()));
        $this->assertFalse($this->sut->checkBalance());
    }

    public function testYouCanGetTheTotalTransactionAmountIfTheTransactionIsBalanced()
    {
        $this->assertEquals(1226, $this->sut->getAmount()->get());
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testIfTheTransactionIsNotBalancedGettingTheTotalTransactionAmountWillThrowAnException()
    {
        $amount = Factory::create('gbp', 0.1);
        $this->sut->addEntry(new Entry(new Nominal('2000'), $amount, AccountType::CR()))
            ->getAmount();
    }

    public function testYouCanGetTheTransactionNote()
    {
        $this->assertInstanceOf('chippyash\Type\String\StringType', $this->sut->getNote());
    }

    public function testYouCanGetTheTransactionDatetime()
    {
        $this->assertInstanceOf('DateTime', $this->sut->getDate());
    }

    public function testASplitTransactionIsSimpleIfItHasOneDrAndOneCrEntry()
    {
        $this->assertTrue($this->sut->isSimple());
        $amount = Factory::create('gbp', 0.1);
        $this->sut->addEntry(new Entry(new Nominal('2000'), $amount, AccountType::CR()));
        $this->assertFalse($this->sut->isSimple());
    }
}
