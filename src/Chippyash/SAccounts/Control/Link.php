<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts\Control;

use Chippyash\Type\String\StringType;
use SAccounts\Nominal;

/**
 * A Control Account Link entry
 */
class Link
{
    /**
     * @var StringType
     */
    protected $name;

    /**
     * @var Nominal
     */
    protected $id;

    /**
     * Constructor
     *
     * @param StringType $name
     * @param Nominal $id
     */
    public function __construct(StringType $name, Nominal $id)
    {
        $this->name = $name;
        $this->id= $id;
    }

    /**
     * @return StringType
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Nominal
     */
    public function getId()
    {
        return $this->id;
    }
}