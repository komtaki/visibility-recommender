<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Commands;

use Komtaki\VisibilityRecommender\Converters\AddConstVisibilityConverter;
use Komtaki\VisibilityRecommender\Exceptions\RuntimeException;
use Komtaki\VisibilityRecommender\FileSystem\FileGenerator;

use const PHP_EOL;

final class RecommendConstVisibility implements CommandInterface
{
    /** @var string[] */
    private $autoloadDirs;

    /** @var string */
    private $targetDir;

    /**
     * @param string[] $autoloadDirs
     */
    public function __construct(array $autoloadDirs, string $targetDir)
    {
        $this->autoloadDirs = $autoloadDirs;
        $this->targetDir = $targetDir;
    }

    public function run(): void
    {
        $converter = new AddConstVisibilityConverter(
            $this->autoloadDirs
        );

        try {
            $fileGenerator = new FileGenerator($converter);
            $fileGenerator->generate($this->targetDir);
        } catch (RuntimeException $e) {
            echo $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }
}
