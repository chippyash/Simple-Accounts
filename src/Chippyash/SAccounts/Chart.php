<?php
/**
 * Simple Double Entry Accounting
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2015, UK
 * @license GPL V3+ See LICENSE.md
 */

namespace SAccounts;

use Assembler\Assembler;
use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Chippyash\Identity\Identifying;
use Chippyash\Identity\Identifiable;
use Monad\FTry;
use Monad\FTry\Success;
use Monad\Match;
use Monad\Option;
use Tree\Node\Node;

/**
 * A Chart of Accounts
 */
class Chart implements Identifiable
{
    use Identifying;

    /**@+
     * Exception error messages
     */
    const ERR_INVALAC = 'Invalid account identifier';
    const ERR_ACEXISTS = 'Account already exists in chart';
    const ERR_NODELETE = 'Cannot delete account: Balance not zero';
    /**@-*/

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
     * @param IntType|null $internalId default == 0
     */
    public function __construct(
        StringType $name,
        Organisation $org,
        Node $tree = null,
        IntType $internalId = null
    ) {
        $this->chartName = $name;
        $this->org = $org;
        $this->tree = Match::on($tree)
            ->Tree_Node_Node($tree)
            ->null(new Node())
            ->value();
        $this->id = (is_null($internalId) ? new IntType(0): $internalId);
    }

    /**
     * Add an account
     *
     * @param Account $account Account to add
     * @param Nominal $parent Optional id of account parent
     *
     * @return $this
     */
    public function addAccount(Account $account, Nominal $parent = null)
    {
        Match::on($this->tryHasNode($account->getNominal(), self::ERR_ACEXISTS))
            ->Monad_FTry_Success(
                Success::create(
                    Match::on($parent)
                        ->SAccounts_Nominal(function ($p) {
                            return $this->findNode($p);
                        })
                        ->null($this->tree)
                        ->value()
                )
            )
            ->value()
            ->pass()
            ->value()
            ->addChild(new Node($account));

        return $this;
    }

    /**
     * Delete an account
     *
     * @param Nominal $nId Id of account
     *
     * @return $this
     */
    public function delAccount(Nominal $nId)
    {
        Assembler::create()
            ->accnt(function () use ($nId){
                return $this->tryGetNode($nId, self::ERR_INVALAC)
                    ->pass()
                    ->flatten();
            })
            ->account( function ($accnt) {
                return FTry::with(function () use ($accnt) {
                    $account = $accnt->getValue();
                    if ($account->getBalance()->get() !== 0) {
                        throw new AccountsException(self::ERR_NODELETE);
                    }
                    return $account;
                })
                    ->pass()
                    ->flatten();
            })
            ->transact(function ($account) {
                Match::on(Option::create(
                    (($account->getType()->getValue() & AccountType::DR) == AccountType::DR), false)
                )
                    ->Monad_Option_Some($account->debit($account->getDebit()->negate()))
                    ->Monad_Option_None($account->credit($account->getCredit()->negate()));
            })
            ->removeChild(function ($accnt) {
                $accnt->getParent()->removeChild($accnt);
            })
            ->assemble();

        return $this;
    }

    /**
     * Get an account from the chart
     *
     * @param Nominal $nId
     *
     * @return Account|null
     */
    public function getAccount(Nominal $nId)
    {
        return Match::on($this->tryGetNode($nId, self::ERR_INVALAC))
            ->Monad_FTry_Success(function ($account) {
                return FTry::with($account->flatten()->getValue());
            })
            ->value()
            ->pass()
            ->value();
    }

    /**
     * Does this chart have specified account
     *
     * @param Nominal $nId
     * @return bool
     */
    public function hasAccount(Nominal $nId)
    {
        return Match::on(FTry::with(function () use ($nId) {
            $this->getAccount($nId);
        }))
            ->Monad_FTry_Success(true)
            ->Monad_FTry_Failure(false)
            ->value();
    }

    /**
     * Get Id of parent for an account
     *
     * @param Nominal $nId
     * @return null|IntType
     *
     * @throws AccountsException
     */
    public function getParentId(Nominal $nId)
    {
        return Match::on(
            Match::on($this->tryGetNode($nId, self::ERR_INVALAC))
                ->Monad_FTry_Success(function ($node) {
                    return Match::on($node->flatten()->getParent());
                })
                ->value()
                ->pass()
                ->value()
        )
            ->Tree_Node_Node(function ($node) {
                /** @var Account $v */
                $v = $node->getValue();
                return is_null($v) ? null : $v->getNominal();
            })
            ->value();
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
     * Set the Chart's root node
     *
     * @param Node $root
     *
     * @return $this
     */
    public function setRootNode(Node $root)
    {
        $this->tree = $root;

        return $this;
    }

    /**
     * @param Nominal $nId
     * @param $exceptionMessage
     *
     * @return FTry
     */
    protected function tryHasNode(Nominal $nId, $exceptionMessage)
    {
        return FTry::with(function () use ($nId, $exceptionMessage) {
            if (!is_null($this->findNode($nId))) {
                throw new AccountsException($exceptionMessage);
            }
        });
    }

    /**
     * @param Nominal $nId
     * @param $exceptionMessage
     *
     * @return FTry
     */
    protected function tryGetNode(Nominal $nId, $exceptionMessage)
    {
        return FTry::with(function () use ($nId, $exceptionMessage) {
            $node = $this->findNode($nId);
            if (is_null($node)) {
                throw new AccountsException($exceptionMessage);
            }
            return $node;
        });
    }

    /**
     * Find an account node using its id
     *
     * @param Nominal $nId
     * @return node|null
     */
    protected function findNode(Nominal $nId)
    {
        return $this->tree->accept(new NodeFinder($nId));
    }
}
