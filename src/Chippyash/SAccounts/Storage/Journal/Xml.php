<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Storage\Journal;

use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Journal;
use SAccounts\JournalStorageInterface;
use SAccounts\Nominal;
use SAccounts\Transaction\Entry;
use SAccounts\Transaction\SplitTransaction;
use Chippyash\Currency\Factory as CurrencyFactory;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;

/**
 * Xml File Storage for a Journal
 */
class Xml implements JournalStorageInterface
{
    /**
     * Path to journal storage file
     * @var StringType
     */
    protected $filePath;

    /**
     * Normalized path to journal file
     * @var StringType
     */
    protected $journalPath;

    /**
     * Template for new journal definition xml file
     * @var string
     */
    protected $template = <<<EOT
<?xml version="1.0"?>
<journal>
    <definition name="" crcy="GBP" inc="0"/>
    <transactions/>
</journal>
EOT;

    /**
     * @var StringType
     */
    protected $journalName;

    /**
     * Constructor
     *
     * @param StringType $filePath
     * @param StringType $journalName
     */
    public function __construct(StringType $filePath, StringType $journalName = null)
    {
        $this->filePath = $filePath;
        if (!is_null($journalName)) {
            $this->setJournalName($journalName);
        }
    }

    /**
     * Set the journal that we will next be working with
     *
     * @param StringType $name
     *
     * @return $this
     */
    public function setJournalName(StringType $name)
    {
        $this->journalName = $name;
        $this->normalizeFilePath($name);

        return $this;
    }

    /**
     * Write Journal definition to store
     * side effect: will set current journal
     *
     * @param Journal $journal
     *
     * @return bool
     */
    public function writeJournal(Journal $journal)
    {
        $this->setJournalName($journal->getName());
        if (file_exists($this->journalPath->get())) {
            return $this->amendJournal($journal);
        } else {
            return $this->createJournal($journal);
        }
    }

    /**
     * Read journal definition from store
     *
     * @return Journal
     * @throws \SAccounts\AccountsException
     */
    public function readJournal()
    {
        if (!isset($this->journalName)) {
            throw new AccountsException('Missing Journal name');
        }
        if (!file_exists($this->journalPath->get())) {
            throw new AccountsException('Missing Journal file');
        }
        //check to make sure Journal is valid
        $attribs = $this->getDefinition();

        $crcy = CurrencyFactory::create($attribs->getNamedItem('crcy')->nodeValue);
        $journal = new Journal($this->journalName, $crcy, $this);

        return $journal;
    }

    /**
     * Write a transaction to store
     *
     * @param SplitTransaction $transaction
     *
     * @return IntType Transaction Unique Id
     * @throws \SAccounts\AccountsException
     */
    public function writeTransaction(SplitTransaction $transaction)
    {
        if (!isset($this->journalName)) {
            throw new AccountsException('Missing Journal name');
        }

        $dom = $this->getDom();
        $xpath = new \DOMXPath($dom);
        $def = $xpath->query('/journal/definition')->item(0);
        $attribs = $def->attributes;
        $txnId = (intval($attribs->getNamedItem('inc')->nodeValue)) + 1;
        $attribs->getNamedItem('inc')->nodeValue = $txnId;

        $transactions = $xpath->query('/journal/transactions')->item(0);

        $newTxn = $dom->createElement('transaction');
        $newTxn->setAttribute('id', $txnId);
        //NB - although we are looking for an ISO801 format to match xs:datetime
        //the W3C format actually matches xsd datetime. PHP ISO8601 does not
        $newTxn->setAttribute('date', $transaction->getDate()->format(\DateTime::W3C));
        $newTxn->setAttribute('note', $transaction->getNote()->get());

        $this->writeSplitFromTransaction($transaction, $dom, $newTxn);
        $transactions->appendChild($newTxn);

        $dom->save($this->journalPath->get());

        return new IntType($txnId);
    }

    /**
     * Read a transaction from store
     *
     * @param IntType $id Transaction Unique Id
     *
     * @return SplitTransaction|null
     * @throws \SAccounts\AccountsException
     */
    public function readTransaction(IntType $id)
    {
        if (!isset($this->journalName)) {
            throw new AccountsException('Missing Journal name');
        }

        $dom = $this->getDom();
        $xpath = new \DOMXPath($dom);
        
        $nodes = $xpath->query("/journal/transactions/transaction[@id='{$id}']");
        if ($nodes->length !== 1) {
            return null;
        }
        
        $txn = $nodes->item(0);

        $crcy = $xpath->query('/journal/definition')->item(0)->attributes->getNamedItem('crcy')->nodeValue;

        return $this->createTransactionFromElement($txn, $crcy, $xpath);
    }

    /**
     * Return all transactions for an account from store
     *
     * @param Nominal $nominal Account Nominal code
     *
     * @return array[SplitTransaction,...]
     */
    public function readTransactions(Nominal $nominal)
    {
        $dom = $this->getDom();
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("/journal/transactions/transaction/split[@nominal='{$nominal}']/..");
        if ($nodes->length === 0){
            return array();
        }

        $crcy = $xpath->query('/journal/definition')->item(0)->attributes->getNamedItem('crcy')->nodeValue;
        $transactions = array();

        foreach ($nodes as $node) {
            $transactions[] = $this->createTransactionFromElement($node, $crcy, $xpath);
        }

        return $transactions;
    }

    /**
     * Create transaction from dom element
     *
     * @param \DOMElement $txn
     * @param $crcyCode
     * @param \DOMXPath
     *
     * @return SplitTransaction
     */
    protected function createTransactionFromElement(\DOMElement $txn, $crcyCode, \DOMXPath $xpath)
    {
        $drNodes = $xpath->query("./split[@type='DR']", $txn);
        $transaction = (new SplitTransaction(
            new \DateTime($txn->attributes->getNamedItem('date')->nodeValue),
            new StringType($txn->attributes->getNamedItem('note')->nodeValue)
        ))
            ->setId(
                new IntType($txn->attributes->getNamedItem('id')->nodeValue)
        );
        foreach ($drNodes as $drNode) {
            $transaction
                ->addEntry(
                    new Entry(
                        new Nominal($drNode->attributes->getNamedItem('nominal')->nodeValue),
                        CurrencyFactory::create($crcyCode)
                            ->set(intval($drNode->attributes->getNamedItem('amount')->nodeValue)),
                        AccountType::DR()
                    )
                );
        }

        $crNodes = $xpath->query("./split[@type='CR']", $txn);
        foreach ($crNodes as $crNode) {
            $transaction
                ->addEntry(
                    new Entry(
                        new Nominal($crNode->attributes->getNamedItem('nominal')->nodeValue),
                        CurrencyFactory::create($crcyCode)
                            ->set(intval($crNode->attributes->getNamedItem('amount')->nodeValue)),
                        AccountType::CR()
                    )
                );
        }

        return $transaction;
    }

    /**
     * Set the normalized journal file name
     * @param StringType $journalName
     */
    protected function normalizeFilePath(StringType $journalName)
    {
        $this->journalPath = new StringType($this->filePath . '/' . strtolower(str_replace(' ', '-', $journalName)) . '.xml');
    }

    /**
     * Amend existing journal definition
     *
     * @param Journal $journal
     * @return bool
     */
    protected function amendJournal(Journal $journal)
    {
        $dom = new \DOMDocument();
        $dom->load($this->journalPath->get());

        return $this->updateJournalContent($dom, $journal);
    }

    /**
     * Create a new journal definition
     *
     * @param Journal $journal
     * @return bool
     */
    protected function createJournal(Journal $journal)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($this->template);

        return $this->updateJournalContent($dom, $journal);
    }

    /**
     * Update content of journal definition
     *
     * @param \DOMDocument $dom
     * @param Journal $journal
     * @return bool
     */
    protected function updateJournalContent(\DOMDocument $dom, Journal $journal)
    {
        $xpath = new \DOMXPath($dom);
        $defNode = $xpath->query('/journal/definition')->item(0);
        $attributes = $defNode->attributes;
        $attributes->getNamedItem('name')->nodeValue = $journal->getName()->get();
        $attributes->getNamedItem('crcy')->nodeValue = $journal->getCurrency()->getCode();

        return ($dom->save($this->journalPath->get()) !== false);
    }

    /**
     * Read and validate a journal definition
     *
     * @return \DOMNamedNodeMap
     */
    protected function getDefinition()
    {
        $xpath = new \DOMXPath($this->getDom());

        return $xpath->query('/journal/definition')->item(0)->attributes;
    }

    /**
     * Get journal definition as Dom
     *
     * @return \DOMDocument
     * @throws AccountsException
     */
    protected function getDom()
    {
        $dom = new \DOMDocument();
        $dom->load($this->journalPath->get());
        $schemaPath = realpath(__DIR__ . '/../../definitions/journal-definition.xsd');

        libxml_use_internal_errors(true);
        if (!$dom->schemaValidate($schemaPath)) {
            $err = libxml_get_last_error()->message;
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            throw new AccountsException('Definition does not validate: ' . $err);
        }

        libxml_use_internal_errors(false);

        return $dom;
    }

    /**
     * Break transaction into its splits and write them out
     *
     * @param SplitTransaction $transaction
     * @param \DOMDocument $dom
     * @param \DOMElement $newTxn
     */
    protected function writeSplitFromTransaction(SplitTransaction $transaction, \DOMDocument $dom, \DOMElement $newTxn)
    {
        foreach ($transaction->getEntries() as $entry) {
            $this->writeSplit(
                $dom,
                $newTxn,
                ($entry->getType()->getValue() == AccountType::CR ? 'CR' : 'DR'),
                $entry->getAmount()->get(),
                $entry->getId()->get()
            );
        }
    }

    /**
     * Write a transaction split to dom
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $txn
     * @param string $type
     * @param int $amount
     * @param string $nominal
     */
    protected function writeSplit(\DOMDocument $dom, \DOMElement $txn, $type, $amount, $nominal)
    {
        $split = $dom->createElement('split');
        $split->setAttribute('type', $type);
        $split->setAttribute('amount', $amount);
        $split->setAttribute('nominal', $nominal);
        $txn->appendChild($split);
    }
}