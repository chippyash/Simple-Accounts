<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts;

use SAccounts\Transaction\SimpleTransaction;

/**
 * A Simple Journal transaction type
 * one DR account - one CR Account
 *
 * @deprecated Use SAccounts\Transaction\SimpleTransaction
 */
class Transaction extends SimpleTransaction
{
}