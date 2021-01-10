<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\Converters\Printers\PrinterInterface;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use Komtaki\VisibilityRecommender\FileSystem\FileRecursiveSearcher;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use function file_get_contents;

class MultiCollectClassConstFetchVisitor
{
    /** @var PrinterInterface */
    private $printer;

    /** @var CollectClassConstFetchVisitor */
    private $collectClassConstFetchVisitor;

    /** @var string[] */
    private $autoloadDirs = [];

    /**
     * @param string[] $autoloadDirs
     */
    public function __construct(array $autoloadDirs, PrinterInterface $printer)
    {
        $this->autoloadDirs = $autoloadDirs;
        $this->printer = $printer;

        $this->collectClassConstFetchVisitor = new CollectClassConstFetchVisitor();
    }

    public function loadClassConstFetch(): void
    {
        $fileSearcher = new FileRecursiveSearcher();

        foreach ($this->autoloadDirs as $autoloadDir) {
            foreach ($fileSearcher->getFileSystemPath($autoloadDir) as $filePath) {
                $code = file_get_contents($filePath);
                if (! $code) {
                    continue;
                }

                $this->collectClassConstFetch($code);
            }
        }

        $this->collectClassConstFetchVisitor->fixProtectedClassConstFetchesIfNotOwnConst();
        $this->collectClassConstFetchVisitor->fixProtectedClassConstFetchesIfSameConstName();
    }

    private function collectClassConstFetch(string $code): void
    {
        $stmts = $this->printer->getAst($code);

        $traverser = new NodeTraverser();

        $nameResolver = new NameResolver(null, [
            'preserveOriginalNames' => true,
            'replaceNodes' => true,
        ]);
        $traverser->addVisitor($nameResolver);

        $traverser->addVisitor($this->collectClassConstFetchVisitor);

        $traverser->traverse($stmts);
    }

    /**
     * @return array<string, array<string, ClassConstFetchType>>
     */
    public function getClassConstFetchTypes(): array
    {
        return $this->collectClassConstFetchVisitor->getClassConstFetchTypes();
    }
}
