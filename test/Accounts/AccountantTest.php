<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Test\Accounts;

use chippyash\Accounts\Accountant;
use chippyash\Accounts\Chart;
use chippyash\Accounts\Nominal;
use chippyash\Accounts\Organisation;
use chippyash\Currency\Factory;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;


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

    protected function setUp()
    {
        $this->fileClerk = $this->getMock('chippyash\Accounts\AccountStorageInterface');
        $this->sut = new Accountant($this->fileClerk);
    }

    public function testAnAccountantCanFileAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp')));
        $this->assertInstanceOf('chippyash\Accounts\Accountant', $this->sut->fileChart($chart));
    }

    public function testAnAccountantCanFetchAChart()
    {
        $chart = new Chart(new StringType('foo bar'), new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp')));
        $this->fileClerk->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($chart));
        $this->assertInstanceOf('chippyash\Accounts\Chart', $this->sut->fetchChart(new StringType('foo bar')));
    }

    public function testAnAccountantCanCreateANewChartOfAccounts()
    {
        $org = new Organisation(new IntType(1), new StringType('Foo Org'), Factory::create('gbp'));
        $def = $this->getMock('chippyash\Accounts\ChartDefinition',array(),array(),'',false);
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
        $this->assertInstanceOf('chippyash\Accounts\Chart', $ret);

        //check accounts are working as expected
        $coaId = new Nominal('0000');
        $bankId = new Nominal('2110');
        $intExpId = new Nominal('7600');

        $this->assertEquals(0, $ret->getAccount($coaId)->getDebit()->get());
        $this->assertEquals(0, $ret->getAccount($coaId)->getCredit()->get());

        $amount = Factory::create($org->getCurrencyCode()->get(), 12.26);
        $ret->getAccount($intExpId)->debit($amount);
        $ret->getAccount($bankId)->credit($amount);
        $this->assertEquals(1226, $ret->getAccount($coaId)->getDebit()->get());
        $this->assertEquals(1226, $ret->getAccount($coaId)->getCredit()->get());
    }

}
