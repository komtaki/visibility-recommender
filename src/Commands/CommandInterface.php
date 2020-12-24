<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Commands;

interface CommandInterface
{
    public function run(): void;
}
