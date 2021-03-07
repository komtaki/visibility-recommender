# komtaki/visibility-recommender

[![Run tests](https://github.com/komtaki/visibility-recommender/workflows/Run%20tests/badge.svg)](https://github.com/komtaki/visibility-recommender/actions?query=workflow%3A%22Run+tests%22)

Analyze of PHP file, it will suggest the recommended visibility on [PSR-12](https://www.php-fig.org/psr/psr-12/) by modifying the constants directly.

>4.3 Properties and Constants
> Visibility MUST be declared on all properties.
>
>Visibility MUST be declared on all constants if your project PHP minimum version supports constant visibilities (PHP 7.1 or later).
>
> https://www.php-fig.org/psr/psr-12/#43-properties-and-constants

The recommended access modifiers are `public`, `private` and `protected`.

# Feature

- Three kinds of `public, protected, private` can be given to public object constants automatically.
- Only minimal changes are required, and all line breaks and spaces are preserved.
- Supported files
    - Mixed classes with and without namespaces.
    - A mixture of constants with and without access modifiers.
    - Plain view files.
- Not supported
    - Functions that can recover constants by string concatenation, such as `eval()` and `constant()`.

## Roughly pattern

Of course, we don't know if the constant reference is constructed by string concatenation using [eval](https://www.php.net/manual/ja/function.eval.php).

### public

- Constants are fetched by the unique class name other than `self`, `parent`, and `static`.

### protected

- Constants are fetched by `self`, `parent` and `static` from inherit classes.
- Constants are fetched by `static` from own classes.
- Constants with the same name are declared in parent and  children classes in the inherit relationship.

### private

- Constants that do not fit into any of the above patterns. Following example.
  - Constants declared in own class and are fetched only in own class by `self`.
  - Constants are seemed to not used from anywhere.

## Installation

    composer install --no-dev

## Sample

### Execution config

```php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Komtaki\VisibilityRecommender\Commands\RecommendConstVisibility;

// The directory or file name where the file you want to modify may be used.
$autoloadDir = [__DIR__ . '/../tests/Fake/FixMe'];

// The directory or file name that you want to modify.
$dirName = __DIR__ . '/../tests/Fake/FixMe';

// Convert
(new RecommendConstVisibility($autoloadDir, $dirName))->run();
```

[./bin/command](./bin/command)

### After

```diff
 class Mail
 {
    // not used
-    const STATUS_YET = 0;
+    private const STATUS_YET = 0;
    // used by command class
-    const STATUS_PROCESS = 1;
+    public const STATUS_PROCESS = 1;
    // not used
-    public const STATUS_DONE = 2;
+    private const STATUS_DONE = 2;
    // used by view
-    const STATUS_CANCEL = 99;
+    public const STATUS_CANCEL = 99;
 }

 class MailCommand
 {
-    const PROTECTED_USE_BY_SELF = true;
+    protected const PROTECTED_USE_BY_SELF = true;

-    const PROTECTED_USE_BY_CHILD = 200;
+    protected const PROTECTED_USE_BY_CHILD = 200;

-    const PROTECTED_USE_BY_GRAND_CHILD = true;
+    protected const PROTECTED_USE_BY_GRAND_CHILD = true;

 class ExtendsMailCommand extends MailCommand
 {
-    const PROTECTED_OVERRIDE = false;
+    protected const PROTECTED_OVERRIDE = false;

class NestExtendsMailCommand extends ExtendsMailCommand
{
-    const PROTECTED_OVERRIDE =true;
+    protected const PROTECTED_OVERRIDE =true;

```

### Target file before execution


```
./tests/Fake/FixMe/
├── Mail.php
├── commands
│   ├── ExtendsMailCommand.php
│   ├── MailCommand.php
│   └── NestExtendsMailCommand.php
└── views
    └── index.php
```

```php
declare(strict_types=1);

class Mail
{
    // not used
    const STATUS_YET = 0;
    // used by command class
    const STATUS_PROCESS = 1;
    // not used
    public const STATUS_DONE = 2;
    // used by view
    const STATUS_CANCEL = 99;
}

```

```php
class MailCommand
{
    const PROTECTED_USE_BY_SELF = true;

    const PROTECTED_USE_BY_CHILD = 200;

    const PROTECTED_USE_BY_GRAND_CHILD = true;

    public function run()
    {
        echo Mail::STATUS_PROCESS;
    }

    public function getStatus()
    {
        return static::PROTECTED_USE_BY_SELF;
    }
}
```

```php
class ExtendsMailCommand extends MailCommand
{
    const PROTECTED_OVERRIDE = false;

    public function run()
    {
        return self::PROTECTED_USE_BY_CHILD;
    }
}
```

```php
class NestExtendsMailCommand extends ExtendsMailCommand
{
    const PROTECTED_OVERRIDE =true;

    public function run()
    {
        return self::PROTECTED_USE_BY_GRAND_CHILD;
    }
}
```

```php
<p><?php echo Mail::STATUS_CANCEL; ?></p>
```

## Available Commands for development

    composer test              // Run unit test
    composer tests             // Test and quality checks
    composer cs-fix            // Fix the coding style
    composer phpstan           // Run phpstan
    composer psalm             // Run psalm
    composer run-script --list // List all commands
