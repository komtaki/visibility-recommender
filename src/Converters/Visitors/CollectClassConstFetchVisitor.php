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

use function array_keys;
use function in_array;
use function strtolower;

final class CollectClassConstFetchVisitor extends GetClassNameVisitor
{
    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

    /** @var array<string, array<string, ClassConstFetchType>> */
    private $protectedClassConstFetchTypes = [];

    /** @var array<string, array{extends: string, constList:string[]}> */
    private $classConstDefinitions = [];

    /** @var string[] */
    private $ownClassConstList = [];

    /** @var string */
    private $extendsClassName = '';

    /** @var string */
    private $className = '';

    private const BUILD_IN_CONST_NAME = 'class';

    private const SPECIAL_CLASS_NAME_STATIC = 'static';

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

    private function createOwnClassConst(Class_ $node): void
    {
        foreach ($node->getConstants() as $consts) {
            foreach ($consts->consts as $const) {
                $this->ownClassConstList[] = $const->name->toString();
            }
        }

        $this->classConstDefinitions[$this->getClassName($this->className)] = [
            'extends' => $this->extendsClassName,
            'constList' => $this->ownClassConstList,
        ];
    }

    /**
     * @inheritDoc
     */
    public function afterTraverse(array $nodes)
    {
        $this->className = '';
        $this->extendsClassName = '';
        $this->ownClassConstList = [];

        return parent::afterTraverse($nodes);
    }

    private function addClassConstFetchTypes(ClassConstFetch $node): void
    {
        if (! $node->class instanceof Name || ! $node->name instanceof Identifier) {
            return;
        }

        $constName = $node->name->toString();

        if (strtolower($constName) === self::BUILD_IN_CONST_NAME) {
            return;
        }

        $className = $node->class->toString();

        // public const pattern
        if (isset($this->classConstFetchTypes[$className][$constName]) && $this->classConstFetchTypes[$className][$constName] instanceof PublicClassConstFetch) {
            return;
        }

        if (! $node->class->isSpecialClassName()) {
            $this->classConstFetchTypes[$className][$constName] = new PublicClassConstFetch();

            return;
        }

        // own const pattern
        if (in_array($constName, $this->ownClassConstList, true)) {
            $currentClassName = $this->getClassName($this->className);
            if (isset($this->classConstFetchTypes[$currentClassName][$constName])) {
                return;
            }

            if (strtolower($node->class->getFirst()) === self::SPECIAL_CLASS_NAME_STATIC) {
                // It is a constant of its own class and has dependency resolution.
                $this->classConstFetchTypes[$currentClassName][$constName] = new ProtectedClassConstFetch();

                return;
            }

            $this->classConstFetchTypes[$currentClassName][$constName] = new PrivateClassConstFetch();

            return;
        }

        // protected const pattern
        if (
            isset($this->classConstFetchTypes[$this->extendsClassName][$constName])
            && $this->classConstFetchTypes[$this->extendsClassName][$constName] instanceof PublicClassConstFetch
        ) {
            return;
        }

        $protectedType = new ProtectedClassConstFetch();
        $this->classConstFetchTypes[$this->extendsClassName][$constName] = $protectedType;
        $this->protectedClassConstFetchTypes[$this->extendsClassName][$constName] = $protectedType;
    }

    /**
     * protectedのものを、定義と突合して孫継承のケースなどで付け替える
     */
    public function fixProtectedClassConstFetchesIfNotOwnConst(): void
    {
        foreach (array_keys($this->protectedClassConstFetchTypes) as $classNameKey) {
            foreach (array_keys($this->protectedClassConstFetchTypes[$classNameKey]) as $constName) {
                $definitionConst = $this->classConstDefinitions[$classNameKey]['constList'] ?? [];
                // 直前の親に定義された定数参照
                if (in_array($constName, $definitionConst, true)) {
                    continue;
                }

                // 直前の親の親~に定義された定数参照
                $extendsClassName = $this->classConstDefinitions[$classNameKey]['extends'] ?? '';
                if (empty($extendsClassName)) {
                    continue;
                }

                while (true) {
                    $extendsDefinitionConst = $this->classConstDefinitions[$extendsClassName] ?? [];
                    if (empty($extendsDefinitionConst)) {
                        break;
                    }

                    if (! in_array($constName, $extendsDefinitionConst['constList'], true)) {
                        $extendsClassName = $this->classConstDefinitions[$extendsClassName]['extends'];
                        continue;
                    }

                    if (isset($this->classConstFetchTypes[$extendsClassName][$constName]) && $this->classConstFetchTypes[$extendsClassName][$constName] instanceof PublicClassConstFetch) {
                        break;
                    }

                    $this->classConstFetchTypes[$extendsClassName][$constName] = $this->classConstFetchTypes[$classNameKey][$constName];
                    unset($this->classConstFetchTypes[$classNameKey][$constName]);
                    break;
                }
            }
        }
    }

    /**
     * 使用されていないように見えるクラス定数で、同名の定数が親にある場合は、protectedにする
     */
    public function fixProtectedClassConstFetchesIfSameConstName(): void
    {
        foreach (array_keys($this->classConstDefinitions) as $classNameKey) {
            if (empty($this->classConstDefinitions[$classNameKey]['constList'])) {
                continue;
            }

            foreach ($this->classConstDefinitions[$classNameKey]['constList'] as $constName) {
                $definitionConst = $this->classConstFetchTypes[$classNameKey][$constName] ?? null;

                if ($definitionConst instanceof PublicClassConstFetch || $definitionConst instanceof ProtectedClassConstFetch) {
                    continue;
                }

                $extendsClassName = $this->classConstDefinitions[$classNameKey]['extends'] ?? [];

                if (empty($extendsClassName)) {
                    continue;
                }

                while (true) {
                    $extendsDefinitionConst = $this->classConstDefinitions[$extendsClassName] ?? [];
                    if (empty($extendsDefinitionConst)) {
                        break;
                    }

                    if (! in_array($constName, $extendsDefinitionConst['constList'], true)) {
                        $extendsClassName = $this->classConstDefinitions[$extendsClassName]['extends'];
                        continue;
                    }

                    $this->classConstFetchTypes[$extendsClassName][$constName] = new ProtectedClassConstFetch();
                    $this->classConstFetchTypes[$classNameKey][$constName] = new ProtectedClassConstFetch();
                    break;
                }
            }
        }
    }

    /**
     * @return array<string, array<string, ClassConstFetchType>>
     */
    public function getClassConstFetchTypes(): array
    {
        return $this->classConstFetchTypes;
    }
}
