<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\Visitors;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

class GetClassNameVisitor extends NodeVisitorAbstract
{
    /** @var string */
    private $nameSpace = '';

    /**
     * @inheritDoc
     */
    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_ && $node->name instanceof Name) {
                $this->nameSpace = $node->name->toString();

                return null;
            }
        }

        return null;
    }

    protected function getClassName(string $className): string
    {
        return $this->nameSpace ? "{$this->nameSpace}\\{$className}" : $className;
    }
}
