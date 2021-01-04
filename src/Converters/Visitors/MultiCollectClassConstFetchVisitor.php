<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\Printers\PrinterInterface;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use Komtaki\VisibilityRecommender\FileSystem\FileRecursiveSearcher;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use function array_keys;
use function file_get_contents;
use function in_array;

class MultiCollectClassConstFetchVisitor
{
    /** @var PrinterInterface */
    private $printer;

    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

    /** @var string[] */
    private $autoloadDirs = [];

    /**
     * @param string[] $autoloadDirs
     */
    public function __construct(array $autoloadDirs, PrinterInterface $printer)
    {
        $this->autoloadDirs = $autoloadDirs;
        $this->printer = $printer;
    }

    public function loadClassConstFetch(): void
    {
        $fileSearcher = new FileRecursiveSearcher();
        $collectClassConstFetchVisitor = new CollectClassConstFetchVisitor();

        foreach ($this->autoloadDirs as $autoloadDir) {
            foreach ($fileSearcher->getFileSystemPath($autoloadDir) as $filePath) {
                $code = file_get_contents($filePath);
                if (! $code) {
                    continue;
                }

                $this->collectClassConstFetch($code, $collectClassConstFetchVisitor);
                $collectClassConstFetchVisitor->resetUniqueClassData();
            }
        }

        $this->classConstFetchTypes = $collectClassConstFetchVisitor->getClassConstFetchTypes();
        $protectedClassConstFetchTypes = $collectClassConstFetchVisitor->getProtectedClassConstFetchTypes();
        $classConstDefinitions = $collectClassConstFetchVisitor->getClassConstDefinitions();

        $this->cleaningProtectedClassConstFetches($classConstDefinitions, $protectedClassConstFetchTypes);
    }

    private function collectClassConstFetch(string $code, CollectClassConstFetchVisitor $collectClassConstFetchVisitor): void
    {
        $stmts = $this->printer->getAst($code);

        $traverser = new NodeTraverser();

        $nameResolver = new NameResolver(null, [
            'preserveOriginalNames' => true,
            'replaceNodes' => true,
        ]);
        $traverser->addVisitor($nameResolver);

        $traverser->addVisitor($collectClassConstFetchVisitor);

        $traverser->traverse($stmts);
    }

    /**
     * protectedのものを、定義と突合して孫継承のケースなどで付け替える
     *
     * @param array<string, array{extends: string, constList:string[]}> $classConstDefinitions
     * @param array<string, array<string, ClassConstFetchType>>         $protectedClassConstFetchTypes
     */
    private function cleaningProtectedClassConstFetches(array $classConstDefinitions, array $protectedClassConstFetchTypes): void
    {
        foreach (array_keys($protectedClassConstFetchTypes) as $classNameKey) {
            foreach (array_keys($protectedClassConstFetchTypes[$classNameKey]) as $constName) {
                $definitionConst = $classConstDefinitions[$classNameKey]['constList'] ?? [];
                // 直前の親に定義された定数参照
                if (in_array($constName, $definitionConst, true)) {
                    continue;
                }

                // 直前の親の親~に定義された定数参照
                $extendsClassName = $classConstDefinitions[$classNameKey]['extends'] ?? '';
                if (empty($extendsClassName)) {
                    continue;
                }

                while (true) {
                    $extendsDefinitionConst = $classConstDefinitions[$extendsClassName] ?? [];
                    if (empty($extendsDefinitionConst)) {
                        break;
                    }

                    if (in_array($constName, $extendsDefinitionConst['constList'], true)) {
                        $this->classConstFetchTypes[$extendsClassName][$constName] = $this->classConstFetchTypes[$classNameKey][$constName];
                        unset($this->classConstFetchTypes[$classNameKey][$constName]);
                        break;
                    }

                    $extendsClassName = $classConstDefinitions[$extendsClassName]['extends'];
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
