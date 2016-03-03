<?php
/**
 * Accounts
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace Chippyash\Test\SAccounts\Helper;


use Chippyash\Currency\Factory;
use Chippyash\Type\BoolType;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use SAccounts\Account;
use SAccounts\Accountant;
use SAccounts\AccountType;
use SAccounts\Chart;
use SAccounts\ChartDefinition;
use SAccounts\Control\Link;
use SAccounts\Control\Links;
use SAccounts\Helper\AccountCloser;
use SAccounts\Nominal;
use SAccounts\Organisation;
use org\bovigo\vfs\vfsStream;

class AccountCloserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System Under Test
     * @var AccountCloser
     */
    protected $sut;
    /**
     * @var Accountant
     */
    protected $accountant;

    /**
     * @var AccountStorageInterface Mock
     */
    protected $fileClerk;
    /**
     * @var JournalStorageInterface Mock
     */
    protected $journalist;

    /**
     * @var Chart
     */
    protected $chart;

    protected function setUp()
    {
        $this->fileClerk = $this->getMock('SAccounts\AccountStorageInterface');
        $this->journalist = $this->getMock('SAccounts\JournalStorageInterface');
        $this->accountant = new Accountant($this->fileClerk);
        $this->chart = $this->setupChart();

        $this->sut = new AccountCloser();
    }
    
    public function testYouCanCloseAnAccountWithAChart()
    {
        $controlAccounts = new Links(
            [
                new Link(new StringType('incomex'), new Nominal('1000')),
                new Link(new StringType('expense'), new Nominal('2000')),
                new Link(new StringType('close'), new Nominal('3000'))
            ]
        );

        $this->sut->closeAccounts(
            $this->chart,
            $controlAccounts,
            new \DateTime(),
            new BoolType(true)
        );
    }

    protected function setupChart()
    {
        $xml = <<< EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="chart-definition.xsd"
        name="Personal">
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

        $root = vfsStream::setup();
        $file = vfsStream::newFile('test.xml')
            ->withContent($xml)
            ->at($root);
        $chart = $this->accountant->createChart(
            new StringType('Foo Chart'),
            new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp')),
            new ChartDefinition(new StringType($file->url()))
        );

        return $chart;
    }
}
