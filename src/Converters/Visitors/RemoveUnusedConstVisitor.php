<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\NodeTraverser;

final class RemoveUnusedConstVisitor extends GetClassNameVisitor
{
    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

    /** @var string */
    private $className = '';

    /**
     * @param array<string, array<string, ClassConstFetchType>> $classConstFetchTypes
     */
    public function __construct(array $classConstFetchTypes)
    {
        $this->classConstFetchTypes = $classConstFetchTypes;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_ && $node->name instanceof Identifier) {
            $this->className = $this->getClassName($node->name->toString());
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($this->className && $node instanceof ClassConst && ! $this->isUsedConst($node)) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }

    private function isUsedConst(ClassConst $classConst): bool
    {
        $constName = $classConst->consts[0]->name->toString();

        return isset($this->classConstFetchTypes[$this->className][$constName]);
    }
}
