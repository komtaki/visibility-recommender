<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\ValueObjects;

use PhpParser\Node\Stmt\Class_;

class PrivateClassConstFetch extends ClassConstFetchType
{
    public function __construct()
    {
        parent::__construct(Class_::MODIFIER_PRIVATE);
    }
}
