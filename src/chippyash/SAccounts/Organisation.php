<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;


use chippyash\Currency\Currency;
use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;

class Organisation
{
    /**
     * @var IntType
     */
    protected $id;

    /**
     * @var StringType
     */
    protected $name;

    /**
     * @var Currency
     */
    protected $crcy;

    public function __construct(IntType $id, StringType $name, Currency $crcy)
    {
        $this-> id = $id;
        $this->name = $name;
        $this->crcy = $crcy;
    }

    /**
     * @return IntType
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StringType
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->crcy;
    }

    /**
     * @return StringType
     */
    public function getCurrencyCode()
    {
        return $this->crcy->getCode();
    }
}