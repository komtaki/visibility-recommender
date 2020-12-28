<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\ValueObjects;

use Komtaki\VisibilityRecommender\Exceptions\RuntimeException;
use PhpParser\Node\Stmt\Class_;

use function in_array;

class ClassConstFetchType
{
    /** @var int */
    private $type;

    private const SUPPORT_TYPE = [
        Class_::MODIFIER_PUBLIC,
        Class_::MODIFIER_PROTECTED,
        Class_::MODIFIER_PRIVATE,
    ];

    public function __construct(int $type = Class_::MODIFIER_PUBLIC)
    {
        if (! in_array($type, self::SUPPORT_TYPE, true)) {
            throw new RuntimeException();
        }

        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function isPublic(): bool
    {
        return $this->type === Class_::MODIFIER_PUBLIC;
    }
}
