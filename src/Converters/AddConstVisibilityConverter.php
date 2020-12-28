<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters;

use Komtaki\VisibilityRecommender\Converters\Printers\Php7PreservingPrinter;
use Komtaki\VisibilityRecommender\Converters\Printers\PrinterInterface;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use Komtaki\VisibilityRecommender\Converters\Visitors\AddClassConstVisibilityVisitor;
use Komtaki\VisibilityRecommender\Converters\Visitors\CollectClassConstFetchVisitor;
use Komtaki\VisibilityRecommender\FileSystem\FileRecursiveSearcher;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use function array_merge;
use function file_get_contents;

final class AddConstVisibilityConverter implements ConverterInterface
{
    /** @var PrinterInterface */
    private $printer;

    /** @var ClassConstFetchType[] */
    private $classConstFetchTypes = [];

    /**
     * @param string[] $autoloadDir
     */
    public function __construct(array $autoloadDir, ?PrinterInterface $printer = null)
    {
        $this->printer = $printer ?? new Php7PreservingPrinter();

        $this->loadClassConstFetch($autoloadDir);
    }

    /**
     * @inheritDoc
     */
    public function convert(string $filePath, string $code): string
    {
        $stmts = $this->printer->getAst($code);

        $stmts = $this->addVisibilityConst($stmts);

        return $this->printer->print($stmts);
    }

    /**
     * @param Node[] $stmts
     *
     * @return Node[]
     */
    private function addVisibilityConst(array $stmts): array
    {
        $traverser = new NodeTraverser();
        $visitor = new AddClassConstVisibilityVisitor($this->classConstFetchTypes);
        $traverser->addVisitor($visitor);

        return $traverser->traverse($stmts);
    }

    /**
     * @param string[] $autoloadDirs
     */
    private function loadClassConstFetch(array $autoloadDirs): void
    {
        $fileSearcher = new FileRecursiveSearcher();
        foreach ($autoloadDirs as $autoloadDir) {
            foreach ($fileSearcher->getFileSystemPath($autoloadDir) as $filePath) {
                $code = file_get_contents($filePath);
                if (! $code) {
                    continue;
                }

                $stmts = $this->printer->getAst($code);

                $traverser = new NodeTraverser();

                $nameResolver = new NameResolver(null, [
                    'preserveOriginalNames' => true,
                    'replaceNodes' => true,
                ]);
                $traverser->addVisitor($nameResolver);

                $visitor = new CollectClassConstFetchVisitor();
                $traverser->addVisitor($visitor);

                $traverser->traverse($stmts);

                $this->classConstFetchTypes = array_merge(
                    $this->classConstFetchTypes,
                    $visitor->getClassConstFetchTypes()
                );
            }
        }
    }
}
