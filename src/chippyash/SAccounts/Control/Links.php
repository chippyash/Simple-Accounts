<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts\Control;

use chippyash\Type\String\StringType;
use Monad\Collection;
use Monad\Match;
use Monad\Option;

/**
 * A Collection of Control Account Links
 */
class Links extends Collection
{
    /**
     * @var StringType
     */
    protected $name;

    /**
     * Constructor
     *
     * Takes array of Links and creates internal structure of associated array using
     * each Entry name as the key
     *
     * @param array $values Array[Control] of initial control account entries
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values, 'SAccounts\Control\Link');
        $assocValues = [];
        foreach ($this->value as $value) {
            $assocValues[$value->getName()->get()] = $value;
        }
        $this->value =$assocValues;
    }

    /**
     * Returns a new Collection with new entry joined to end of this Collection
     *
     * @param Link $link
     *
     * @return Links
     */
    public function addEntry(Link $link)
    {
        return $this->vUnion(new static([$link]))->setName($this->name);
    }

    /**
     * @param StringType $name
     * @return $this
     */
    public function setName(StringType $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return name of this Control Links Collection
     * @return StringType
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get a Link entry using its name
     *
     * @param StringType $name
     * @return Link|null
     */
    public function getLink(StringType $name)
    {
        return Match::create(Option::create(isset($this[$name()]), false))
            ->Monad_Option_Some($this[$name()])
            ->Monad_Option_None(function(){return null;})
            ->value();
    }

    /**
     * Get a Link entry's nominal id using its name
     *
     * @param StringType $name
     * @return Nominal|null
     */
    public function getLinkId(StringType $name)
    {
        return Match::create(Option::create($this->getLink($name)))
            ->Monad_Option_Some(function($val){return $val->value()->getId();})
            ->Monad_Option_None(function(){return null;})
            ->value();
    }
}