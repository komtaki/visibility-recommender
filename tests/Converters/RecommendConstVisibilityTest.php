<?php

declare(strict_types=1);

namespace Komtaki\VisibilityRecommender;

use Komtaki\VisibilityRecommender\Converters\AddConstVisibilityConverter;
use Komtaki\VisibilityRecommender\Exceptions\NotFoundFileException;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class RecommendConstVisibilityTest extends TestCase
{
    /** @var AddConstVisibilityConverter */
    protected $converter;

    private const FIXME_MAIL_DIR_PATH = __DIR__ . '/../Fake/FixMe/';

    private const FIXME_MAIL_COMMAND_FILE_PATH = __DIR__ . '/../Fake/FixMe/MailCommand.php';

    private const FIXME_MAIL_FILE_PATH = __DIR__ . '/../Fake/FixMe/Mail.php';

    private const RESULT_MAIL_COMMAND_FILE_PATH = __DIR__ . '/../Fake/Result/MailCommand.php';

    private const RESULT_MAIL_FILE_PATH = __DIR__ . '/../Fake/Result/Mail.php';

    /**
     * @test
     */
    public function 定数が他のクラスで未使用の時、privateの定数が定義される(): void
    {
        $actual = (new AddConstVisibilityConverter([self::FIXME_MAIL_DIR_PATH]))->convert(
            self::FIXME_MAIL_FILE_PATH,
            $this->getFileContent(self::FIXME_MAIL_FILE_PATH)
        );

        $expect = $this->getFileContent(self::RESULT_MAIL_FILE_PATH);

        $this->assertSame($expect, $actual);
    }

    /**
     * @test
     */
    public function 定数が使われている時、publicの定数が定義される(): void
    {
        $actual = (new AddConstVisibilityConverter([self::FIXME_MAIL_DIR_PATH]))->convert(
            self::FIXME_MAIL_COMMAND_FILE_PATH,
            $this->getFileContent(self::FIXME_MAIL_COMMAND_FILE_PATH)
        );

        $expect = $this->getFileContent(self::RESULT_MAIL_COMMAND_FILE_PATH);

        $this->assertSame($expect, $actual);
    }

    /**
     * @test
     */
    public function 定数が継承先でのみ使われている時、protectedの定数が定義される(): void
    {
        $actual = (new AddConstVisibilityConverter([self::FIXME_MAIL_DIR_PATH]))->convert(
            self::FIXME_MAIL_COMMAND_FILE_PATH,
            $this->getFileContent(self::FIXME_MAIL_COMMAND_FILE_PATH)
        );

        $expect = $this->getFileContent(self::RESULT_MAIL_COMMAND_FILE_PATH);

        $this->assertSame($expect, $actual);
    }

    private function getFileContent(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new NotFoundFileException();
        }

        return $content;
    }
}
