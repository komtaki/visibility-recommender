<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender;

use PHPUnit\Framework\TestCase;

class VisibilityRecommenderTest extends TestCase
{
    /** @var VisibilityRecommender */
    protected $visibilityRecommender;

    protected function setUp(): void
    {
        $this->visibilityRecommender = new VisibilityRecommender();
    }

    public function testIsInstanceOfVisibilityRecommender(): void
    {
        $actual = $this->visibilityRecommender;
        $this->assertInstanceOf(VisibilityRecommender::class, $actual);
    }
}
