<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;

use function in_array;

final class CollectClassConstFetchVisitor extends GetClassNameVisitor
{
    /** @var array<string, array<string, ClassConstFetchType>> */
    private static $classConstFetchTypes = [];

    /** @var array<string, array<string, ClassConstFetchType>> */
    private static $protectedClassConstFetchTypes = [];

    /** @var array<string, array{extends: string, constList:string[]}> */
    private static $classConstDefinitions = [];

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
            if ($node->name instanceof Identifier) {
                $this->className = $node->name->toString();
                if ($node->extends instanceof Name) {
                    $this->extendsClassName = $node->extends->toString();
                }

                $this->createOwnClassConst($node);
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

        if (isset(self::$classConstFetchTypes[$className][$constName]) && self::$classConstFetchTypes[$className][$constName]->isPublic()) {
            return;
        }

        if (! $node->class->isSpecialClassName()) {
            self::$classConstFetchTypes[$className][$constName] = new ClassConstFetchType();

            return;
        }

        if (in_array($constName, $this->ownClassConstList, true)) {
            $privateType = new ClassConstFetchType(Class_::MODIFIER_PRIVATE);
            self::$classConstFetchTypes[$this->getClassName($this->className)][$constName] = $privateType;

            return;
        }

        if (
            isset(self::$classConstFetchTypes[$this->extendsClassName][$constName])
            && self::$classConstFetchTypes[$this->extendsClassName][$constName]->isPublic()
        ) {
            return;
        }

        $protectedType = new ClassConstFetchType(Class_::MODIFIER_PROTECTED);
        self::$classConstFetchTypes[$this->extendsClassName][$constName] = $protectedType;
        self::$protectedClassConstFetchTypes[$this->extendsClassName][$constName] = $protectedType;
    }

    private function createOwnClassConst(Class_ $node): void
    {
        foreach ($node->getConstants() as $consts) {
            foreach ($consts->consts as $const) {
                $this->ownClassConstList[] = $const->name->toString();
            }
        }

        self::$classConstDefinitions[$this->getClassName($this->className)] = [
            'extends' => $this->extendsClassName,
            'constList' => $this->ownClassConstList,
        ];
    }

    /**
     * @return array<string, array{extends: string, constList:string[]}>
     */
    public static function getClassConstDefinitions(): array
    {
        return self::$classConstDefinitions;
    }

    /**
     * @return array<string, array<string, ClassConstFetchType>>
     */
    public static function getProtectedClassConstFetchTypes(): array
    {
        return self::$protectedClassConstFetchTypes;
    }

    /**
     * @return array<string, array<string, ClassConstFetchType>>
     */
    public static function getClassConstFetchTypes(): array
    {
        return self::$classConstFetchTypes;
    }
}
