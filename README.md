# komtaki/visibility-recommender

[![Run tests](https://github.com/komtaki/visibility-recommender/workflows/Run%20tests/badge.svg)](https://github.com/komtaki/visibility-recommender/actions?query=workflow%3A%22Run+tests%22)

PHPのプログラムを解析して、[PSR-12](https://www.php-fig.org/psr/psr-12/)で推奨されているアクセス修飾子を定数を直接修正して提案します。

>4.3 Properties and Constants
> Visibility MUST be declared on all properties.
>
>Visibility MUST be declared on all constants if your project PHP minimum version supports constant visibilities (PHP 7.1 or later).
>
> https://www.php-fig.org/psr/psr-12/#43-properties-and-constants

付与される修飾子は、`public`, `private`, `protected`です。

## Installation

    composer install

## Sample

### Execution config

```php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Komtaki\VisibilityRecommender\Commands\RecommendConstVisibility;

// 修正したいファイルが使用されている可能性のあるディレクトリ or ファイル名
$autoloadDir = [__DIR__ . '/../tests/Fake/FixMe'];

// 修正したいファイル or 修正したファイルのあるディレクトリ
$dirName = __DIR__ . '/../tests/Fake/FixMe';

// 変換
(new RecommendConstVisibility($autoloadDir, $dirName))->run();
```

[./bin/command](./bin/command)

### After

```diff
 class ExtendsMailCommand extends MailCommand
 {
-    const HOGE ='hoge';
+    private const HOGE ='hoge';

     public function actionIndex()
     {

 class Mail
 {
     // 状態
-    const STATUS_YET = 0;
-    const STATUS_PROCESS = 1;
-    const STATUS_DONE = 2;
-    const STATUS_CANCEL = 99;
+    private const STATUS_YET = 0;
+    public const STATUS_PROCESS = 1;
+    private const STATUS_DONE = 2;
+    private const STATUS_CANCEL = 99;
 }

 class MailCommand
 {
-    const SLEEP_SPAN = 200;
+    protected const SLEEP_SPAN = 200;

     public function actionIndex()
     {

```

### Target file before execution

```php
class ExtendsMailCommand extends MailCommand
{
    const HOGE ='hoge';

    public function actionIndex()
    {
        return self::SLEEP_SPAN;
    }
}

```

```php

declare(strict_types=1);

class BatchMail
{
    // 状態
    const STATUS_YET = 0;
    const STATUS_PROCESS = 1;
    const STATUS_DONE = 2;
    const STATUS_CANCEL = 99;
}

```

```php

class MailCommand
{
    const SLEEP_SPAN = 200;

    public function actionIndex()
    {
        return Mail::STATUS_PROCESS;
    }
}

```

## Available Commands for development

    composer test              // Run unit test
    composer tests             // Test and quality checks
    composer cs-fix            // Fix the coding style
    composer phpstan           // Run phpstan
    composer psalm             // Run psalm
    composer run-script --list // List all commands
