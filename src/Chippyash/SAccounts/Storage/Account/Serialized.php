<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts\Storage\Account;

use Chippyash\Type\Number\IntType;
use SAccounts\AccountsException;
use SAccounts\AccountStorageInterface;
use SAccounts\Chart;
use Chippyash\Type\String\StringType;

/**
 * Serialized PHP Account storage method
 */
class Serialized implements AccountStorageInterface
{
    /**
     * @var StringType
     */
    protected $baseDir;

    /**
     * @param StringType $baseDir Path to storage files
     *
     * @throws AccountsException
     */
    public function __construct(StringType $baseDir)
    {
        if (!file_exists($baseDir())) {
            throw new AccountsException("Invalid directory: {$baseDir}");
        }
        $this->baseDir = $baseDir;
    }

    /**
     * Fetch a chart from storage
     *
     * @param StringType $name
     * @param IntType $orgId that the chart belongs to
     *
     * @return Chart
     * 
     * @throws AccountsException
     */
    public function fetch(StringType $name, IntType $orgId)
    {
        $fName = $this->normalizeName($name, $orgId);
        if (!file_exists($fName)) {
            throw new AccountsException('Chart storage file does not exist: ' . $fName);
        }

        return unserialize(file_get_contents($fName));
    }

    /**
     * Send a chart to storage
     *
     * @param Chart $chart
     * @return bool
     */
    public function send(Chart $chart)
    {
        return (
            file_put_contents(
                $this->normalizeName($chart->getName(),$chart->getOrg()->getId()),
                serialize($chart)) > 0
        );
    }

    /**
     * Normalize name for filing
     *
     * @param StringType $name
     * @param IntType|null $orgId that the chart belongs to. default == ''
     *
     * @return string
     */
    protected function normalizeName(StringType $name, IntType $orgId)
    {
        return $this->baseDir . '/' . strtolower(
            str_replace(
                ' ', '_', "{$name}_{$orgId}")
            )
            . '.saccount';
    }
}