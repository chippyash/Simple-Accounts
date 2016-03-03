<?php
/**
 * Accounts
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts\Transaction;

use Chippyash\Currency\Factory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\Nominal;
use SAccounts\Transaction\SimpleTransaction;

class SimpleTransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleTransaction
     */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new SimpleTransaction(new Nominal('0000'), new Nominal('1000'), Factory::create('gbp', 12.26));
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
        $sut = new SimpleTransaction(new Nominal('0000'), new Nominal('1000'), Factory::create('gbp', 12.26), $note);
        $this->assertEquals($note, $sut->getNote());
    }

    public function testYouCanSetAnOptionalDateOnConstruction()
    {
        $note = new StringType('foo bar');
        $dt = new \DateTime();
        $sut = new SimpleTransaction(new Nominal('0000'), new Nominal('1000'), Factory::create('gbp', 12.26), $note, $dt);
        $this->assertEquals($dt, $sut->getDate());
    }

    public function testConstructingATransactionDoesNotSetItsId()
    {
        $this->assertNull($this->sut->getId());
    }

    public function testYouCanSetAndGetAnId()
    {
        $id = new IntType(1);
        $this->assertEquals($id, $this->sut->setId($id)->getId());
    }

    public function testYouCanGetTheDebitAccountCode()
    {
        $this->assertEquals('0000', $this->sut->getDrAc()[0]->get());
    }

    public function testYouCanGetTheCreditAccountCode()
    {
        $this->assertEquals('1000', $this->sut->getCrAc()[0]->get());
    }

    public function testYouCanGetTheTransactionAmount()
    {
        $this->assertEquals(1226, $this->sut->getAmount()->get());
    }

    public function testYouCanGetTheTransactionNote()
    {
        $this->assertInstanceOf('Chippyash\Type\String\StringType', $this->sut->getNote());
    }

    public function testYouCanGetTheTransactionDatetime()
    {
        $this->assertInstanceOf('DateTime', $this->sut->getDate());
    }
}
