<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts;

use SAccounts\Account;
use SAccounts\Accountant;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\Journal;
use SAccounts\Nominal;
use SAccounts\Organisation;
use SAccounts\Transaction;
use Chippyash\Currency\Factory as CurrencyFactory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;


class AccountantTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Accountant
     */
    protected $sut;

    /**
     * Mock
     * @var AccountStorageInterface
     */
    protected $fileClerk;

    /**
     * Mock
     * @var JournalStorageInterface
     */
    protected $journalist;

    protected function setUp()
    {
        $this->fileClerk = $this->getMock('SAccounts\AccountStorageInterface');
        $this->journalist = $this->getMock('SAccounts\JournalStorageInterface');
        $this->sut = new Accountant($this->fileClerk);
    }

    public function testAnAccountantCanFileAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp')));
        $this->fileClerk->expects($this->once())
            ->method('send')
            ->will($this->returnValue(true));
        $this->assertInstanceOf('SAccounts\Accountant', $this->sut->fileChart($chart));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testAnAccountantWillThrowExceptionIfItCannotFileAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp')));
        $this->fileClerk->expects($this->once())
            ->method('send')
            ->will($this->returnValue(false));
        $this->sut->fileChart($chart);
    }

    public function testAnAccountantCanFetchAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp')));
        $this->fileClerk->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($chart));
        $this->assertInstanceOf(
            'SAccounts\Chart',
            $this->sut->fetchChart(new StringType('foo bar'), new IntType(1))
        );
    }

    public function testAnAccountantCanCreateANewChartOfAccounts()
    {
        $org = new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp'));
        $def = $this->getMockBuilder('SAccounts\ChartDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="chart-definition.xsd"
        name="Personal">
    <account id="1" nominal="0000" type="real" name="COA" status="active">
        <account id="2" nominal="1000" type="real" name="Balance Sheet" status="active">
            <account id="3" nominal="2000" type="asset" name="Assets" status="active">
                <account id="4" nominal="2100" type="bank" name="At Bank" status="active">
                    <account id="5" nominal="2110" type="bank" name="Current Account" status="active"/>
                    <account id="6" nominal="2120" type="bank" name="Savings Account" status="active"/>
                </account>
            </account>
            <account id="7" nominal="3000" type="liability" name="Liabilities" status="active">
                <account id="8" nominal="3100" type="equity" name="Equity" status="active">
                    <account id="9" nominal="3110" type="equity" name="Opening Balance" status="active"/>
                </account>
                <account id="10" nominal="3200" type="liability" name="Loans" status="active">
                    <account id="11" nominal="3210" type="liability" name="Mortgage" status="active"/>
                </account>
            </account>
        </account>
        <account id="12" nominal="5000" type="real" name="Profit And Loss" status="active">
            <account id="13" nominal="6000" type="income" name="Income" status="active">
                <account id="14" nominal="6100" type="income" name="Salary" status="active"/>
                <account id="15" nominal="6200" type="income" name="Interest Received" status="active"/>
            </account>
            <account id="16" nominal="7000" type="expense" name="Expenses" status="active">
                <account id="17" nominal="7100" type="expense" name="House" status="active"/>
                <account id="18" nominal="7200" type="expense" name="Travel" status="active"/>
                <account id="19" nominal="7300" type="expense" name="Insurance" status="active"/>
                <account id="20" nominal="7400" type="expense" name="Food" status="active"/>
                <account id="21" nominal="7500" type="expense" name="Leisure" status="active"/>
                <account id="22" nominal="7600" type="expense" name="Interest Payments" status="active"/>
            </account>
        </account>
    </account>
</chart>
EOT;
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $def->expects($this->once())
            ->method('getDefinition')
            ->willReturn($dom);
        $ret = $this->sut->createChart(new StringType('Personal'), $org, $def);
        $this->assertInstanceOf('SAccounts\Chart', $ret);

        //check accounts are working as expected
        $coaId = new Nominal('0000');
        $bankId = new Nominal('2110');
        $intExpId = new Nominal('7600');

        $this->assertEquals(0, $ret->getAccount($coaId)->getDebit()->get());
        $this->assertEquals(0, $ret->getAccount($coaId)->getCredit()->get());

        $amount = CurrencyFactory::create($org->getCurrencyCode()->get(), 12.26);
        $ret->getAccount($intExpId)->debit($amount);
        $ret->getAccount($bankId)->credit($amount);
        $this->assertEquals(1226, $ret->getAccount($coaId)->getDebit()->get());
        $this->assertEquals(1226, $ret->getAccount($coaId)->getCredit()->get());
    }

    public function testYouCanSetAnOptionalJournalist()
    {
        $this->assertInstanceOf('SAccounts\Accountant', $this->sut->setJournalist($this->journalist));
    }

    public function testYouCanCreateAJournalIfJournalistIsSet()
    {
        $this->sut->setJournalist($this->journalist);
        $crcy = CurrencyFactory::create('gbp');
        $this->assertInstanceOf('SAccounts\Journal', $this->sut->createJournal(new StringType('Foo Bar'), $crcy));
    }

    /**
     * @expectedException \SAccounts\JournalException
     */
    public function testCreatingAJournalWithoutAJournalistWillThrowException()
    {
        $crcy = CurrencyFactory::create('gbp');
        $this->sut->createJournal(new StringType('Foo Bar'), $crcy);
    }

    public function testYouCanFileAJournalToStorage()
    {
        $this->sut->setJournalist($this->journalist);
        $this->journalist->expects($this->once())
            ->method('writeJournal')
            ->will($this->returnValue(true));
        $crcy = CurrencyFactory::create('gbp');
        $journal = new Journal(new StringType('Foo Bar'), $crcy, $this->journalist);
        $this->assertInstanceOf('SAccounts\Accountant', $this->sut->fileJournal($journal));
    }

    /**
     * @expectedException \SAccounts\JournalException
     */
    public function testFilingAJournalToStorageWhenJournalistNotSetThrowsException()
    {
        $crcy = CurrencyFactory::create('gbp');
        $journal = new Journal(new StringType('Foo Bar'), $crcy, $this->journalist);
        $this->sut->fileJournal($journal);
    }

    /**
     * @expectedException \SAccounts\JournalException
     */
    public function testFilingAJournalToStorageThrowsExceptionIfJournalistFailsToWrite()
    {
        $this->sut->setJournalist($this->journalist);
        $this->journalist->expects($this->once())
            ->method('writeJournal')
            ->will($this->returnValue(false));
        $crcy = CurrencyFactory::create('gbp');
        $journal = new Journal(new StringType('Foo Bar'), $crcy, $this->journalist);
        $this->sut->fileJournal($journal);
    }

    public function testYouCanFetchAJournalFromStorage()
    {
        $this->sut->setJournalist($this->journalist);
        $crcy = CurrencyFactory::create('gbp');
        $journal = new Journal(new StringType('Foo Bar'), $crcy, $this->journalist);
        $this->journalist->expects($this->once())
            ->method('readJournal')
            ->will($this->returnValue($journal));
        $this->assertInstanceOf('SAccounts\Journal', $this->sut->fetchJournal(new StringType('Foo bar')));
    }

    /**
     * @expectedException \SAccounts\JournalException
     */
    public function testFetchingAJournalFromStorageWithNoJournalistSetWillThrowException()
    {
        $this->sut->fetchJournal(new StringType('Foo bar'));
    }

    public function testYouCanWriteATransactionToAJournalAndUpdateAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp')));
        $chart->addAccount(new Account($chart, new Nominal('0000'),AccountType::DR(), new StringType('Foo')));
        $chart->addAccount(new Account($chart, new Nominal('0001'),AccountType::CR(), new StringType('Bar')));
        $journal = new Journal(new StringType('Foo Journal'), CurrencyFactory::create('gbp'), $this->journalist);
        $txn = new Transaction(new Nominal('0000'), new Nominal('0001'), CurrencyFactory::create('gbp', 12.26));
        $this->journalist->expects($this->once())
            ->method('writeTransaction')
            ->will($this->returnValue(new IntType(1)));

        $returnedTransaction = $this->sut->writeTransaction($txn, $chart, $journal);
        $this->assertEquals(1, $returnedTransaction->getId()->get());
        $this->assertEquals(1226, $chart->getAccount(new Nominal('0000'))->getDebit()->get());
        $this->assertEquals(1226, $chart->getAccount(new Nominal('0001'))->getCredit()->get());
    }
}
