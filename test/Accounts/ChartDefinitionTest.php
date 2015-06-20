<?php
/**
 * Simple Double Entry Accounting
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;

use SAccounts\ChartDefinition;
use chippyash\Type\String\StringType;
use org\bovigo\vfs\vfsStream;

class ChartDefinitionTest extends \PHPUnit_Framework_TestCase {

    protected $sut;

    protected $filePath;

    protected $xmlFile = <<<EOT
<?xml version="1.0"?>
<root><foo bar="2"/></root>
EOT;

    protected function setUp()
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('test.xml')
            ->withContent($this->xmlFile)
            ->at($root);
        $this->filePath = $file->url();
    }

    public function testCanConstructWithValidFileName()
    {
        $this->assertInstanceOf('SAccounts\ChartDefinition', new ChartDefinition(new StringType($this->filePath)));
    }

    /**
     * @expectedException SAccounts\AccountsException
     */
    public function testConstructionWithInvalidFileNameWillThrowException()
    {
        $a = new ChartDefinition(new StringType('foo'));
    }

    public function testConstructionWithValidFileNameWillReturnClass()
    {
        $sut = new ChartDefinition(new StringType($this->filePath));
        $this->assertInstanceOf('SAccounts\ChartDefinition', $sut);
    }

    /**
     * @expectedException SAccounts\AccountsException
     */
    public function testGettingTheDefinitionWillThrowExceptionIfDefinitionFileIsInvalidXml()
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('test2.xml')
            ->withContent('')
            ->at($root);
        $sut = new ChartDefinition(new StringType($file->url()));
        $sut->getDefinition();
    }

    /**
     * @expectedException SAccounts\AccountsException
     */
    public function testGettingDefinitionWillThrowExceptionIfDefinitionFailsValidation()
    {
        $sut = new ChartDefinition(new StringType($this->filePath));
        $this->assertInstanceOf('DOMDocument', $sut->getDefinition());
    }

    public function testGettingTheDefinitionWillReturnADomDocumentWithValidDefinitionFile()
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<chart name="Personal">
    <account nominal="0000" type="real" name="COA">
        <account nominal="1000" type="real" name="Balance Sheet"/>
    </account>
</chart>
EOT;

        $root = vfsStream::setup();
        $file = vfsStream::newFile('test3.xml')
            ->withContent($xml)
            ->at($root);
        $sut = new ChartDefinition(new StringType($file->url()));
        $this->assertInstanceOf('DOMDocument', $sut->getDefinition());
    }

}
