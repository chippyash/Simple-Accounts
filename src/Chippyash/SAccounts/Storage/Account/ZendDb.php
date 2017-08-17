<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Ltd, 2017, UK
 * @license   Proprietary See LICENSE.md
 */
namespace chippyash\Chippyash\SAccounts\Storage\Account;

use Chippyash\Type\String\StringType;
use SAccounts\AccountStorageInterface;
use SAccounts\Chart;
use Zend\Db\Adapter\AdapterInterface;

/**
 * Account chart storage using ZendDb to store in a database
 */
class ZendDb implements AccountStorageInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Fetch a chart from storage
     *
     * @param StringType $name
     *
     * @return Chart
     */
    public function fetch(StringType $name)
    {
        // TODO: Implement fetch() method.
    }

    /**
     * Send a chart to storage
     *
     * @param Chart $chart
     *
     * @return bool
     */
    public function send(Chart $chart)
    {
        // TODO: Implement send() method.
    }
}