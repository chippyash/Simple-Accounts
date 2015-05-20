<?php
/**
 * Simple Double Entry Accounting

 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace chippyash\Accounts;

use chippyash\Type\Number\IntType;
use chippyash\Type\String\StringType;
use chippyash\Accounts\Organisation;
use Tree\Node\Node;
use Tree\Visitor\YieldVisitor;

/**
 * A Chart of Accounts
 */
class Chart 
{
    /**
     * Tree of accounts
     * @var Node
     */
    protected $tree;

    /**
     * Organisation that owns this chart
     * @var Organisation
     */
    protected $org;

    /**
     * Name of this chart
     * @var StringType
     */
    protected $chartName;

    /**
     * Constructor
     *
     * @param StringType $name Chart Name
     * @param Organisation $org Organisation that owns this chart
     * @param Node $tree Tree of accounts
     */
    public function __construct(StringType $name, Organisation $org, Node $tree = null)
    {
        $this->chartName = $name;
        $this->org = $org;
        if (is_null($tree)) {
            $this->tree = new Node();
        } else {
            $this->tree = $tree;
        }
    }

    /**
     * Add an account
     *
     * @param Account $ac Account to add
     * @param Nominal $parent Optional id of account parent
     *
     * @return $this
     * @throws AccountsException
     */
    public function addAccount(Account $ac, Nominal $parent = null)
    {
        if (!is_null($this->findNode($ac->getId()))) {
            throw new AccountsException('Account already exists in chart');
        }

        if (is_null($parent)) {
            $root = $this->tree;
        } else {
            $root = $this->findNode($parent);
        }

        $root->addChild(new Node($ac));

        return $this;
    }

    /**
     * Delete an account
     *
     * @param Nominal $id Id of account
     *
     * @return $this
     * @throws AccountsException
     */
    public function delAccount(Nominal $id)
    {
        $ac = $this->findNode($id);
        if (is_null($ac)) {
            throw new AccountsException('Invalid account identifier');
        }
        //@var Account
        $account = $ac->getValue();
        if ($account->getBalance()->get() !== 0) {
            throw new AccountsException('Cannot delete account: Balance not zero');
        }

        $isDr = (($account->getType()->getValue() & AccountType::DR) == AccountType::DR);
        if ($isDr) {
            $account->debit($account->getDebit()->negate());
        } else {
            $account->credit($account->getCredit()->negate());
        }

        $ac->getParent()->removeChild($ac);

        return $this;
    }

    /**
     * Get an account from the chart
     *
     * @param Nominal $id
     *
     * @return Account|null
     * @throws AccountsException
     */
    public function getAccount(Nominal $id)
    {
        $ac = $this->findNode($id);
        if (is_null($ac)) {
            throw new AccountsException('Invalid account identifier');
        }

        return $ac->getValue();
    }

    /**
     * Does this chart have specified account
     *
     * @param Nominal $id
     * @return bool
     */
    public function hasAccount(Nominal $id)
    {
        try {
            $this->getAccount($id);
            return true;
        } catch (AccountsException $e) {
            return false;
        }
    }

    /**
     * Get Id of parent for an account
     *
     * @param Nominal $id
     * @return null|IntType
     * @throws AccountsException
     */
    public function getParentId(Nominal $id)
    {
        $thisNode = $this->findNode($id);
        if (is_null($thisNode)) {
            throw new AccountsException('Invalid account identifier');
        }

        $prntNode = $thisNode->getParent();
        if (is_null($prntNode) || is_null($prntNode->getValue())) {
            return null;
        } else {
            return $prntNode->getValue()->getId();
        }
    }

    /**
     * Return organisation that owns this chart
     *
     * @return Organisation
     */
    public function getOrg()
    {
        return $this->org;
    }

    /**
     * Return account tree
     *
     * @return Node
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Return chart name
     *
     * @return StringType
     */
    public function getName()
    {
        return $this->chartName;
    }

    /**
     * Find an account node using its id
     *
     * @param Nominal $id
     * @return node|null
     */
    protected function findNode(Nominal $id)
    {
        return $this->tree->accept(new NodeFinder($id));
    }
}