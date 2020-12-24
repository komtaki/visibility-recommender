<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\ValueObjects;

class ConstVisibility
{
    /** @var bool */
    private $isPublic = true;

    /** @var bool */
    private $isProtected = false;

    /** @var bool */
    private $isPrivate = false;

    public function setPublic(): void
    {
        $this->isPublic = true;
        $this->isProtected = false;
        $this->isPrivate = false;
    }

    public function setPrivate(): void
    {
        $this->isPublic = false;
        $this->isProtected = false;
        $this->isPrivate = true;
    }

    public function setProtected(): void
    {
        $this->isPublic = false;
        $this->isProtected = true;
        $this->isPrivate = false;
    }

    public function getPublic(): bool
    {
        return $this->isPublic;
    }

    public function getPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function getProtected(): bool
    {
        return $this->isProtected;
    }
}
