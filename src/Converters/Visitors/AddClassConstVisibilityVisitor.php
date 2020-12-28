<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class AddClassConstVisibilityVisitor extends NodeVisitorAbstract
{
    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

    /** @var string */
    private $nameSpace = '';

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
    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_ && $node->name instanceof Name) {
                $this->nameSpace = $node->name->toString();

                return null;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_ && $node->name instanceof Identifier) {
            $this->fixUnNecessaryPublicConst(
                $node->name->toString(),
                $node->getConstants()
            );

            return NodeTraverser::STOP_TRAVERSAL;
        }

        return null;
    }

    /**
     * @param ClassConst[] $classConsts
     */
    private function fixUnNecessaryPublicConst(string $className, array $classConsts): void
    {
        $className = $this->nameSpace ? "{$this->nameSpace}\\{$className}" : $className;

        foreach ($classConsts as $consts) {
            if (! $consts->isPublic()) {
                continue;
            }

            $constName = $consts->consts[0]->name->toString();

            $fetchTypes = $this->classConstFetchTypes[$className][$constName] ?? new ClassConstFetchType(Class_::MODIFIER_PRIVATE);
            $consts->flags = $fetchTypes->getType();
        }
    }
}
