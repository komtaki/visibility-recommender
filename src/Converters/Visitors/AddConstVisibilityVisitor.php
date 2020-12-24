<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\ValueObjects\ConstVisibility;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class AddConstVisibilityVisitor extends NodeVisitorAbstract
{
    /** @var ConstVisibility[] */
    private $constUsePatterns = [];

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($this->constUsePatterns[$node]) {
            return null;
        }

        return null;
    }

    /**
     * @param ConstVisibility[] $constUsePatterns
     */
    public function setConstUsePattern(array $constUsePatterns): void
    {
        $this->constUsePatterns = $constUsePatterns;
    }
}
