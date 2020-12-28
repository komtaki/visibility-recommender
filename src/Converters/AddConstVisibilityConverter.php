<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters;

use Komtaki\VisibilityRecommender\Converters\Printers\Php7PreservingPrinter;
use Komtaki\VisibilityRecommender\Converters\Printers\PrinterInterface;
use Komtaki\VisibilityRecommender\Converters\ValueObjects\ClassConstFetchType;
use Komtaki\VisibilityRecommender\Converters\Visitors\AddClassConstVisibilityVisitor;
use Komtaki\VisibilityRecommender\Converters\Visitors\MultiCollectClassConstFetchVisitor;
use PhpParser\Node;
use PhpParser\NodeTraverser;

final class AddConstVisibilityConverter implements ConverterInterface
{
    /** @var PrinterInterface */
    private $printer;

    /** @var array<string, array<string, ClassConstFetchType>> */
    private $classConstFetchTypes = [];

    /**
     * @param string[] $autoloadDir
     */
    public function __construct(array $autoloadDir, ?PrinterInterface $printer = null)
    {
        $this->printer = $printer ?? new Php7PreservingPrinter();

        $multiCollectClassConstFetchVisitor = new MultiCollectClassConstFetchVisitor($autoloadDir, $this->printer);
        $multiCollectClassConstFetchVisitor->loadClassConstFetch();
        $this->classConstFetchTypes = $multiCollectClassConstFetchVisitor->getClassConstFetchTypes();
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
}
