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
 * Interface to save and fetch a Chart to/from storage
 */
interface AccountStorageInterface {

    /**
     * Fetch a chart from storage
     *
     * @param StringType $name
     * @return Chart
     */
    public function fetch(StringType $name);

    /**
     * Send a chart to storage
     *
     * @param Chart $chart
     * @return bool
     */
    public function send(Chart $chart);


}