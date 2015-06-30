<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Test\SAccounts\Transaction;

use chippyash\Currency\Factory;
use SAccounts\AccountType;
use SAccounts\Nominal;
use SAccounts\Transaction\Entries;
use SAccounts\Transaction\Entry;

class EntriesTest extends \PHPUnit_Framework_TestCase
{
    public function testYouCanCreateAnEmptyEntriesCollection()
    {
        $this->assertInstanceOf(
            'SAccounts\Transaction\Entries',
            new Entries()
        );
    }

    public function testYouCanCreateAnEntriesCollectionsWithEntryValues()
    {
        $this->assertInstanceOf(
            'SAccounts\Transaction\Entries',
            new Entries(
                array(
                    $this->getEntry('7789', 12.34, 'dr'),
                    $this->getEntry('3456', 6.17, 'cr'),
                    $this->getEntry('2001', 6.17, 'cr'),
                )
            )
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Value 0 is not a SAccounts\Transaction\Entry
     */
    public function testTouCannotCreateAnEntriesCollectionWithNonEntryValues()
    {
        new Entries(array(new \stdClass()));
    }

    public function testYouCanAddAnotherEntryToEntriesAndGetNewEntriesCollection()
    {
        $sut1 = new Entries(
            array(
                $this->getEntry('7789', 12.34, 'dr'),
                $this->getEntry('3456', 6.17, 'cr'),
                $this->getEntry('2001', 6.17, 'cr'),
            )
        );

        $sut2 = $sut1->addEntry($this->getEntry('3333',12.26,'cr'));

        $this->assertInstanceOf('SAccounts\Transaction\Entries', $sut2);
        $this->assertEquals(3, count($sut1));
        $this->assertEquals(4, count($sut2));
        $this->assertTrue($sut1 != $sut2);
    }

    public function testCheckBalanceWillReturnTrueIfEntriesAreBalanced()
    {
        $sut1 = new Entries(
            array(
                $this->getEntry('7789', 12.34, 'dr'),
                $this->getEntry('3456', 6.17, 'cr'),
                $this->getEntry('2001', 6.17, 'cr'),
            )
        );

        $this->assertTrue($sut1->checkBalance());
    }

    public function testCheckBalanceWillReturnFalseIfEntriesAreNotBalanced()
    {
        $sut1 = new Entries(
            array(
                $this->getEntry('7789', 12.34, 'dr'),
                $this->getEntry('3456', 6.17, 'cr'),
            )
        );

        $this->assertFalse($sut1->checkBalance());
    }

    protected function getEntry($id, $amount, $type)
    {
        return new Entry(
            new Nominal($id),
            Factory::create('gbp', $amount),
            ($type == 'dr' ? AccountType::DR() : AccountType::CR())
        );
    }
}
