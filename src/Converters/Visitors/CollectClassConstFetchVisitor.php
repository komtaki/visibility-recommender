<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

use function in_array;

final class CollectClassConstFetchVisitor extends NodeVisitorAbstract
{
    /** @var ClassConstFetchType[] */
    private $classConstFetchTypes = [];

    /** @var string[] */
    private $ownClassConsts = [];

    /** @var string */
    private $extendsClassName = '';

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->createOwnClassConst($node);
            if ($node->extends instanceof Name) {
                $this->extendsClassName = $node->extends->toString();
            }

            return null;
        }

        if ($node instanceof ClassConstFetch) {
            $this->addClassConstFetchPatterns($node);
        }

        return null;
    }

    private function addClassConstFetchPatterns(ClassConstFetch $node): void
    {
        if (! $node->class instanceof Name || ! $node->name instanceof Identifier) {
            return;
        }

        $className = $node->class->toString();
        $constName = $node->name->toString();

        if ($node->class->isSpecialClassName()) {
            if (in_array($constName, $this->ownClassConsts, true)) {
                return;
            }

            $className = $this->extendsClassName;
            if (
                isset($this->classConstFetchTypes["{$className}::{$constName}"])
                && $this->classConstFetchTypes["{$className}::{$constName}"]->gettype() === Class_::MODIFIER_PUBLIC
            ) {
                return;
            }

            $type = new ClassConstFetchType(Class_::MODIFIER_PROTECTED);
        } else {
            $type = new ClassConstFetchType();
        }

        $this->classConstFetchTypes["{$className}::{$constName}"] = $type;
    }

    private function createOwnClassConst(Class_ $node): void
    {
        foreach ($node->getConstants() as $consts) {
            foreach ($consts->consts as $const) {
                $this->ownClassConsts[] = $const->name->toString();
            }
        }
    }

    /**
     * @return ClassConstFetchType[]
     */
    public function getClassConstFetchTypes(): array
    {
        return $this->classConstFetchTypes;
    }
}
