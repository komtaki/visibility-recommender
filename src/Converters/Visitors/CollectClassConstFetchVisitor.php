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
    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

    /** @var array<string, array<string, ClassConstFetchType>> */
    private $protectedClassConstFetchTypes = [];

    /** @var string[] */
    private $ownClassConstList = [];

    /** @var string */
    private $extendsClassName = '';

    /** @var string */
    private $className = '';

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->createOwnClassConst($node);
            if ($node->name instanceof Identifier) {
                $this->className = $node->name->toString();
            }

            if ($node->extends instanceof Name) {
                $this->extendsClassName = $node->extends->toString();
            }

            return null;
        }

        if ($node instanceof ClassConstFetch) {
            $this->addClassConstFetchTypes($node);
        }

        return null;
    }

    private function addClassConstFetchTypes(ClassConstFetch $node): void
    {
        if (! $node->class instanceof Name || ! $node->name instanceof Identifier) {
            return;
        }

        $className = $node->class->toString();
        $constName = $node->name->toString();

        if (isset($this->classConstFetchTypes[$className][$constName]) && $this->classConstFetchTypes[$className][$constName]->isPublic()) {
            return;
        }

        if (! $node->class->isSpecialClassName()) {
            $this->classConstFetchTypes[$className][$constName] = new ClassConstFetchType();

            return;
        }

        if (in_array($constName, $this->ownClassConstList, true)) {
            $privateType = new ClassConstFetchType(Class_::MODIFIER_PRIVATE);
            $this->classConstFetchTypes[$className][$constName] = $privateType;

            return;
        }

        $className = $this->extendsClassName;
        if (
            isset($this->classConstFetchTypes[$className][$constName])
            && $this->classConstFetchTypes[$className][$constName]->isPublic()
        ) {
            return;
        }

        $protectedType = new ClassConstFetchType(Class_::MODIFIER_PROTECTED);
        $this->classConstFetchTypes[$className][$constName] = $protectedType;
        $this->protectedClassConstFetchTypes[$className][$constName] = $protectedType;
    }

    private function createOwnClassConst(Class_ $node): void
    {
        foreach ($node->getConstants() as $consts) {
            foreach ($consts->consts as $const) {
                $this->ownClassConstList[] = $const->name->toString();
            }
        }
    }

    /**
     * @return array<string, array{extends: string, constList:string[]}>
     */
    public function getOwnClassConstList(): array
    {
        if (empty($this->className)) {
            return [];
        }

        return [
            $this->className => [
                'extends' => $this->extendsClassName,
                'constList' => $this->ownClassConstList,
            ],
        ];
    }

    /**
     * @return array<string, array<string, ClassConstFetchType>>
     */
    public function getProtectedClassConstFetchTypes(): array
    {
        return $this->protectedClassConstFetchTypes;
    }

    /**
     * @return array<string, array<string, ClassConstFetchType>>
     */
    public function getClassConstFetchTypes(): array
    {
        return $this->classConstFetchTypes;
    }
}
