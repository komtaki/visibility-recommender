<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters;

use Komtaki\VisibilityRecommender\Converters\Printers\Php7PreservingPrinter;
use Komtaki\VisibilityRecommender\Converters\Printers\PrinterInterface;
use Komtaki\VisibilityRecommender\Converters\Visitors\AddConstVisibilityVisitor;
use Komtaki\VisibilityRecommender\Converters\Visitors\CollectConstUseVisitor;
use Komtaki\VisibilityRecommender\FileSystem\FileRecursiveSearcher;
use Komtaki\VisibilityRecommender\ValueObjects\ConstVisibility;
use PhpParser\Node;
use PhpParser\NodeTraverser;

use function array_merge;
use function file_get_contents;

final class AddConstVisibilityConverter implements ConverterInterface
{
    /** @var PrinterInterface */
    private $printer;

    /** @var ConstVisibility[] */
    private $constUsePatterns = [];

    /**
     * @param string[] $autoloadDir
     */
    public function __construct(array $autoloadDir, ?PrinterInterface $printer = null)
    {
        $this->loadConstUse($autoloadDir);

        $this->printer = $printer ?? new Php7PreservingPrinter();
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
        $visitor = new AddConstVisibilityVisitor();
        $visitor->setConstUsePattern($this->constUsePatterns);
        $traverser->addVisitor($visitor);

        return $traverser->traverse($stmts);
    }

    /**
     * @param string[] $autoloadDirs
     */
    private function loadConstUse(array $autoloadDirs): void
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
                $visitor = new CollectConstUseVisitor();
                $traverser->addVisitor($visitor);
                $traverser->traverse($stmts);

                $this->constUsePatterns = array_merge(
                    $this->constUsePatterns,
                    $visitor->getConstUseList()
                );
            }
        }
    }
}
