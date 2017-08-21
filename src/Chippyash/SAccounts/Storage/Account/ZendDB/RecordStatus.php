<?php
/**
 * Freetimers Web Application Framework
 *
 * @author    Ashley Kitson
 * @copyright Freetimers Communications Ltd, 2017, UK
 * @license   Proprietary See LICENSE.md
 */
namespace SAccounts\Storage\Account\ZendDB;

use MyCLabs\Enum\Enum;

/**
 * Class RecordStatus
 *
 * What state is a  record in?
 *
 * @method RecordStatus ACTIVE()
 * @method RecordStatus SUSPENDED()
 * @method RecordStatus DEFUNCT()
 */
class RecordStatus extends Enum
{
    /**
     * Record is active
     */
    const ACTIVE = 'active';
    /**
     * Record is suspended
     */
    const SUSPENDED = 'suspended';
    /**
     * Record is defunct (no longer in use)
     */
    const DEFUNCT = 'defunct';
}