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
        $this->fileClerk = $this->createMock('SAccounts\AccountStorageInterface');
        $this->journalist = $this->createMock('SAccounts\JournalStorageInterface');
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
        $this->assertInstanceOf('SAccounts\Chart', $this->sut->fetchChart(new StringType('foo bar')));
    }

    public function testAnAccountantCanCreateANewChartOfAccounts()
    {
        $org = new Organisation(new IntType(1), new StringType('Foo Org'), CurrencyFactory::create('gbp'));
        $def = $this->getMockBuilder('SAccounts\ChartDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart name="Personal">
    <account nominal="0000" type="real" name="COA">
        <account nominal="1000" type="real" name="Balance Sheet">
            <account nominal="2000" type="asset" name="Assets">
                <account nominal="2100" type="bank" name="At Bank">
                    <account nominal="2110" type="bank" name="Current Account"/>
                    <account nominal="2120" type="bank" name="Savings Account"/>
                </account>
                <account nominal="3000" type="liability" name="Liabilities">
                    <account nominal="3100" type="equity" name="Equity">
                        <account nominal="3110" type="equity" name="Opening Balance"/>
                    </account>
                    <account nominal="3200" type="liability" name="Loans">
                        <account nominal="3210" type="liability" name="Mortgage"/>
                    </account>
                </account>
            </account>
        </account>
        <account nominal="5000" type="real" name="Profit And Loss">
            <account nominal="6000" type="income" name="Income">
                <account nominal="6100" type="income" name="Salary"/>
                <account nominal="6200" type="income" name="Interest Received"/>
            </account>
            <account nominal="7000" type="expense" name="Expenses">
                <account nominal="7100" type="expense" name="House"/>
                <account nominal="7200" type="expense" name="Travel"/>
                <account nominal="7300" type="expense" name="Insurance"/>
                <account nominal="7400" type="expense" name="Food"/>
                <account nominal="7500" type="expense" name="Leisure"/>
                <account nominal="7600" type="expense" name="Interest Payments"/>
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
