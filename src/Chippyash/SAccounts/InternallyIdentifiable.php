<?php
/**
 * Simple Double Entry Bookkeeping
 *
 * @author    Ashley Kitson
 * @copyright Ashley Kitson, 2017, UK
 * @license   GPL V3+ See LICENSE.md
 */
namespace SAccounts;

/**
 * Interface InternallyIdentifiable
 *
 * A class that has an internal identifier
 */
interface InternallyIdentifiable
{
    /**
     * Return the internal identifier
     *
     * @return IntType
     */
    public function id();
}