<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Converters\ValueObjects;

abstract class ClassConstFetchType
{
    /** @var int */
    private $type;

    public function __construct(int $type)
    {
        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
