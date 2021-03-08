<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\Commands;

use Komtaki\VisibilityRecommender\Converters\RemoveUnusedConstConverter;
use Komtaki\VisibilityRecommender\Exceptions\RuntimeException;
use Komtaki\VisibilityRecommender\FileSystem\FileGenerator;

use const PHP_EOL;

final class RemoveUnusedConst implements CommandInterface
{
    /** @var string[] */
    private $autoloadDir;

    /** @var string */
    private $dirName;

    /**
     * @param string[] $autoloadDir
     */
    public function __construct(array $autoloadDir, string $dirName)
    {
        $this->autoloadDir = $autoloadDir;
        $this->dirName = $dirName;
    }

    public function run(): void
    {
        $converter = new RemoveUnusedConstConverter(
            $this->autoloadDir
        );

        try {
            $fileGenerator = new FileGenerator($converter);
            $fileGenerator->generate($this->dirName);
        } catch (RuntimeException $e) {
            echo $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }
}
