<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use Komtaki\VisibilityRecommender\ValueObjects\ConstVisibility;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CollectConstUseVisitor extends NodeVisitorAbstract
{
    /** @var ConstVisibility[] */
    private $constUsePatternList = [];

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        return null;
    }

    /**
     * @return ConstVisibility[]
     */
    public function getConstUseList(): array
    {
        return $this->constUsePatternList;
    }
}
