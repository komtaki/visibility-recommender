<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender\FileSystem;

use Komtaki\VisibilityRecommender\Exceptions\NotFoundFileException;

use function array_merge;
use function in_array;
use function is_dir;
use function is_file;
use function preg_match;
use function rtrim;
use function scandir;

class FileRecursiveSearcher
{
    private const PHP_PATTERN = '/.*\.php/';

    /**
     * 再帰的にファイルのパスを取得します
     *
     * @param string $fileType デフォルトphpファイルのみ
     *
     * @return string[]
     *
     * @throws NotFoundFileException
     */
    public function getFileSystemPath(string $dir, string $fileType = self::PHP_PATTERN): array
    {
        if (is_file($dir)) {
            return [$dir];
        }

        if (! is_dir($dir)) {
            throw new NotFoundFileException("Not found such a directory. path: ${dir}");
        }

        $list = scandir($dir);
        if (! $list) {
            throw new NotFoundFileException("Not found such a directory. path: ${dir}");
        }

        $results = [];

        foreach ($list as $record) {
            if (in_array($record, ['.', '..'])) {
                continue;
            }

            $path = rtrim($dir, '/') . '/' . $record;
            if (is_file($path) && preg_match($fileType, $path)) {
                $results[] = $path;
            }

            if (is_dir($path)) {
                $results = array_merge($results, $this->getFileSystemPath($path));
            }
        }

        return $results;
    }
}
