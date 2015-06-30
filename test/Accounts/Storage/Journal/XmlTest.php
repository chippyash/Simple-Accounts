<?php
/**
 * SAccounts
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Test\SAccounts\Storage\Journal;

use SAccounts\Journal;
use SAccounts\Nominal;
use SAccounts\Storage\Journal\Xml;
use SAccounts\Transaction;
use chippyash\Currency\Factory as CurrencyFactory;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

class XmlTest extends \PHPUnit_Framework_TestCase {
    /**
     * System Under Test
     * @var Xml
     */
    protected $sut;

    /**
     * @var vfsStreamFile
     */
    protected $root;

    /**
     * @var Journal
     */
    protected $journal;

    protected function setUp()
    {
        $this->root = vfsStream::setup();
        $this->sut = new Xml(new StringType($this->root->url()));

        //we need real ones of these as we will writing to files
        $this->journal = new Journal(new StringType('Foo Journal'), CurrencyFactory::create('gbp'), $this->sut);
    }

    public function testConstructionTakesAnOptionalJournalName()
    {
        $this->assertInstanceOf('SAccounts\Storage\Journal\Xml', new Xml(new StringType($this->root->url()), new StringType('Foo Journal')));
    }

    public function testSuccessfulJournalWriteWillReturnTrue()
    {
        $this->assertTrue($this->sut->writeJournal($this->journal));
    }

    public function testSuccessfulJournalWriteWillStoreJournalDefinitionInXMLFile()
    {
        $expectedFileName = $this->root->url() . '/foo-journal.xml';
        $this->sut->writeJournal($this->journal);
        $this->assertTrue(file_exists($expectedFileName));
    }

    public function testYouCanCreateANewJournalDefinitionFile()
    {
        $expectedFileName = $this->root->url() . '/foo-journal.xml';
        $this->assertFalse(file_exists($expectedFileName));
        $this->sut->writeJournal($this->journal);
        $this->assertTrue(file_exists($expectedFileName));

        $expectedContents = <<<EOT
<?xml version="1.0"?>
<journal>
    <definition name="Foo Journal" crcy="GBP" inc="0"/>
    <transactions/>
</journal>

EOT;
        $this->AssertEquals($expectedContents, file_get_contents($expectedFileName));
    }

    public function testYouCanAmendAnExistingJournalDefinitionFile()
    {
        $this->sut->writeJournal($this->journal);
        $amendedJournal = new Journal(new StringType('Foo Journal'), CurrencyFactory::create('usd'), $this->sut);
        $this->sut->writeJournal($amendedJournal);

        $expectedContents = <<<EOT
<?xml version="1.0"?>
<journal>
    <definition name="Foo Journal" crcy="USD" inc="0"/>
    <transactions/>
</journal>

EOT;
        $expectedFileName = $this->root->url() . '/foo-journal.xml';
        $this->AssertEquals($expectedContents, file_get_contents($expectedFileName));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testNotSettingJournalNameBeforeAReadWillThrowAnException()
    {
        $this->sut->readJournal();
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testReadingAJournalDefinitionWillThrowExceptionIfFileDoesNotExist()
    {
        $this->sut
            ->setJournalName(new StringType('Foo Journal'))
            ->readJournal();
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testReadingAJournalDefinitionWillThrowExceptionIfFileIsNotAJournalDefinition()
    {
        $badContent = <<<EOT
<?xml version="1.0"?>
<journal>
    <transactions/>
</journal>

EOT;
        $file = vfsStream::newFile('foo-journal.xml')
            ->withContent($badContent)
            ->at($this->root);
        $this->sut
            ->setJournalName(new StringType('Foo Journal'))
            ->readJournal();
    }

    public function testReadingAJournalDefinitionWillReturnAJournal()
    {
        $goodContent = <<<EOT
<?xml version="1.0"?>
<journal>
    <definition name="Foo Journal" crcy="GBP" inc="0"/>
    <transactions/>
</journal>

EOT;
        $jFile = vfsStream::newFile('foo-journal.xml')
            ->withContent($goodContent)
            ->at($this->root);

        $this->assertInstanceOf(
            'SAccounts\Journal',
            $this->sut->setJournalName(new StringType('Foo Journal'))->readJournal());
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testNotSettingJournalNameBeforeATransactionWriteWillThrowAnException()
    {
        $this->sut->writeTransaction($this->createTransaction('0000','00001',12.26));
    }

    public function testWritingATransactionWillSaveItToXMLFileAndIncrementTheTransactionSequenceNumber()
    {
        $this->sut->writeJournal($this->journal);
        $txnId = $this->sut->setJournalName(new StringType('Foo Journal'))
            ->writeTransaction($this->createTransaction('0000','00001',12.26));

        $this->assertEquals(new IntType(1), $txnId);

        $expectedFileName = $this->root->url() . '/foo-journal.xml';
        $this->assertFileExists($expectedFileName);
        $testDom = new \DOMDocument();
        $testDom->load($expectedFileName);
        $textXpath = new \DOMXPath($testDom);

        //file has transaction
        $txn = $textXpath->query("/journal/transactions/transaction[@id='1']");
        $this->assertEquals(1, $txn->length);

        //inc === 1
        $inc = $textXpath->query('/journal/definition')
            ->item(0)
            ->attributes
            ->getNamedItem('inc')
            ->nodeValue;
        $this->assertEquals(1, intval($inc));
    }

    /**
     * @expectedException \SAccounts\AccountsException
     */
    public function testNotSettingJournalNameBeforeATransactionReadWillThrowAnException()
    {
        $this->sut->readTransaction(new IntType(99));
    }

    public function testReadingATransactionThatExistsWillReturnATransactionObject()
    {
        $this->sut->writeJournal($this->journal);
        $txnId = $this->sut->setJournalName(new StringType('Foo Journal'))
            ->writeTransaction($this->createTransaction('0000','00001',12.26, 'foo bar'));
        $txn = $this->sut->readTransaction($txnId);
        $this->assertInstanceOf('SAccounts\Transaction\SplitTransaction', $txn);
        $this->assertEquals(1, $txn->getId()->get());
        $this->assertEquals(1226, $txn->getAmount()->get());
        $this->assertEquals('0000', $txn->getDrAc()[0]->get());
        $this->assertEquals('0001', $txn->getCrAc()[0]->get());
        $this->assertEquals('foo bar', $txn->getNote()->get());
    }

    public function testReadingATransactionThatDoesNotExistWillReturnNull()
    {
        $this->sut->writeJournal($this->journal);
        $this->assertNull($this->sut->readTransaction(new IntType(99)));
    }

    public function testReadingTransactionsForAnAccountThatDoesNotExistWillReturnEmptyArray()
    {
        $this->sut->writeJournal($this->journal);
        $this->assertEmpty($this->sut->readTransactions(new Nominal('0000')));
    }

    public function testReadingTransactionsForAnAccountThatDoesExistWillReturnAnArrayOfTransactions()
    {
        $this->sut->writeJournal($this->journal);
        $this->sut->writeTransaction($this->createTransaction('0000','0001',12.26));
        $this->sut->writeTransaction($this->createTransaction('1000','0000',15.99));
        $this->sut->writeTransaction($this->createTransaction('0000','2100',123.67));
        $this->sut->writeTransaction($this->createTransaction('3200','0000',0.26));

        $transactions = $this->sut->readTransactions(new Nominal('0000'));
        $this->assertEquals(4, count($transactions));

        foreach($transactions as $transaction) {
            $this->assertInstanceOf('SAccounts\Transaction\SplitTransaction', $transaction);
        }
    }

    /**
     * Create and return a test transaction
     *
     * @param $dr
     * @param $cr
     * @param $amount
     * @param null $note
     *
     * @return Transaction
     */
    protected function createTransaction($dr, $cr, $amount, $note = null)
    {
        $crcy = CurrencyFactory::create('gbp', $amount);
        if (is_null($note)) {
            $txn = new Transaction(new Nominal($dr), new Nominal($cr), $crcy);
        } else {
            $txn = new Transaction(new Nominal($dr), new Nominal($cr), $crcy, new StringType($note));
        }

        return $txn;
    }
}
