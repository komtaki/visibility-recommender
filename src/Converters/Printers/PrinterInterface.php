<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Printers;

use PhpParser\Node;

interface PrinterInterface
{
    /**
     * @return Node[]
     */
    public function getAst(string $code): array;

    /**
     * @param Node[] $newStmts
     */
    public function print(array $newStmts): string;
}
