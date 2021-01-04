<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\PrivateClassConstFetch;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\NodeTraverser;

final class AddClassConstVisibilityVisitor extends GetClassNameVisitor
{
    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

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
        $className = $this->getClassName($className);

        foreach ($classConsts as $consts) {
            if (! $consts->isPublic()) {
                continue;
            }

            $constName = $consts->consts[0]->name->toString();

            $fetchTypes = $this->classConstFetchTypes[$className][$constName] ?? new PrivateClassConstFetch();
            $consts->flags = $fetchTypes->getType();
        }
    }
}
