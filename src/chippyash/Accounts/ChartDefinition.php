<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Accounts;


use chippyash\Type\String\StringType;

/**
 * Helper to retrieve chart definition xml
 */
class ChartDefinition
{
    /**
     * @var StringType
     */
    protected $xmlFileName;

    /**
     * Constructor
     *
     * @param StringType $xmlFileName
     *
     * @throws AccountsException
     */
    public function __construct(StringType $xmlFileName)
    {
        if (!file_exists($xmlFileName())) {
            throw new AccountsException("Invalid file name: {$xmlFileName}");
        }
        $this->xmlFileName = $xmlFileName;
    }

    /**
     * Get chart definition as a DOMDocument
     *
     * @return \DOMDocument
     * @throws AccountsException
     */
    public function getDefinition()
    {
        set_error_handler(function($number, $error){
            if (preg_match('/^DOMDocument::load\(\): (.+)$/', $error, $m) === 1) {
                throw new AccountsException($m[1]);
            }
        });
        $dom = new \DOMDocument();
        $dom->load($this->xmlFileName->get());

        if (!$dom->schemaValidate(__DIR__ .'/definitions/chart-definition.xsd')) {
            throw new AccountsException('Definition does not validate');
        }

        restore_error_handler();
        return $dom;
    }
}