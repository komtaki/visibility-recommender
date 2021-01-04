<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\PrivateClassConstFetch;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\ProtectedClassConstFetch;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\PublicClassConstFetch;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;

use function in_array;
use function strtolower;

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

        if (strtolower($constName) === 'class') {
            return;
        }

        // public const pattern
        if (isset(self::$classConstFetchTypes[$className][$constName]) && self::$classConstFetchTypes[$className][$constName] instanceof PublicClassConstFetch) {
            return;
        }

        if (! $node->class->isSpecialClassName()) {
            self::$classConstFetchTypes[$className][$constName] = new PublicClassConstFetch();

            return;
        }

        // private const pattern
        if (in_array($constName, $this->ownClassConstList, true)) {
            $currentClassName = $this->getClassName($this->className);
            if (
                isset(self::$classConstFetchTypes[$currentClassName][$constName]) && (
                    self::$classConstFetchTypes[$currentClassName][$constName] instanceof PublicClassConstFetch ||
                    self::$classConstFetchTypes[$currentClassName][$constName] instanceof ProtectedClassConstFetch)
            ) {
                return;
            }

            self::$classConstFetchTypes[$currentClassName][$constName] = new PrivateClassConstFetch();

            return;
        }

        // protected const pattern
        if (
            isset(self::$classConstFetchTypes[$this->extendsClassName][$constName])
            && self::$classConstFetchTypes[$this->extendsClassName][$constName] instanceof PublicClassConstFetch
        ) {
            return;
        }

        $protectedType = new ProtectedClassConstFetch();
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
