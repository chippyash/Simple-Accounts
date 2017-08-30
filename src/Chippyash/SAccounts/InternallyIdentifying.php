<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use Chippyash\Type\Number\IntType;

/**
 * Trait implementing InternallyIdentifiable
 */
trait InternallyIdentifying
{
    /**
     * @var IntType
     */
    protected $internalId;

    /**
     * @return IntType
     */
    public function id()
    {
        return $this->internalId;
    }
}