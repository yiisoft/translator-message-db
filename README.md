<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Translator DB Message Storage</h1>
    <br>
</p>

The package provides message storage backend based on DB to be used 
with [`yiisoft/translator`](https://github.com/yiisoft/translator) package.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/translator-message-db/v/stable.png)](https://packagist.org/packages/yiisoft/translator-message-db)
[![Total Downloads](https://poser.pugx.org/yiisoft/translator-message-db/downloads.png)](https://packagist.org/packages/yiisoft/translator-message-db)
[![Build status](https://github.com/yiisoft/translator-message-db/workflows/build/badge.svg)](https://github.com/yiisoft/translator-message-db/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/translator-message-db/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/translator-message-db/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/translator-message-db/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/translator-message-db/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ftranslator-message-db%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/translator-message-db/master)
[![static analysis](https://github.com/yiisoft/translator-message-db/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/translator-message-db/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/translator-message-db/coverage.svg)](https://shepherd.dev/github/yiisoft/translator-message-db)

## Requirements

- PHP 7.4 or higher.
- `json` PHP extension.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/translator-message-db --prefer-dist
```

## Configuration

### Quick start

**Step 1.** You need a configured database connection (for example using [`yiisoft/db-sqlite`](https://github.com/yiisoft/db-sqlite))
and [`yiisoft/yii-db-migration`](https://github.com/yiisoft/yii-db-migration) package.

Add to `config/params.php`:
```php
...
    'yiisoft/yii-db-migration' => [
        'updateNamespace' => ['Yiisoft\\Translator\\Message\\Db\\migrations'],
    ],
...
```

and run the following command in console for create tables for db storage:

```shell
./yii migrate/up
```

**Step 2.** In case you use [`yiisoft/config`](http://github.com/yiisoft/config), you will get configuration automatically. If not, the following DI container configuration is necessary:

```php
<?php

declare(strict_types=1);

use Yiisoft\Translator\MessageReaderInterface;
use Yiisoft\Translator\Message\Db\MessageSource;

return [
    MessageReaderInterface::class => [
        'class' => MessageSource::class,
    ],
];
```

## General usage

### Create of instance of MessageSource

```php
/** @var \Yiisoft\Db\Connection\ConnectionInterface $db */
$messageSource = new \Yiisoft\Translator\Message\Db\MessageSource($db);
```

### Create of instance of MessageSource with caching

```php
/** @var \Yiisoft\Db\Connection\ConnectionInterface $db */
/** @var \Yiisoft\Cache\CacheInterface $cache */
$cacheDuration = 7200; // The TTL of this value. If set to null, default value is used - 3600
$messageSource = new \Yiisoft\Translator\Message\Db\MessageSource($db, $cache, $cacheDuration);
```

### Read message without `yiisoft/translator` package

```php
/** 
 * @var \Yiisoft\Translator\Message\Db\MessageSource $messageSource
 * @var ?string $translatedString
 */
$id = 'messageIdentificator';
$category = 'messageCategory';
$language = 'de-DE';

$translatedString = $messageSource->getMessage($id, $category, $language);
```

### Writing messages from array to storage

```php
/** 
 * @var \Yiisoft\Translator\Message\Db\MessageSource $messageSource
 */
$category = 'messageCategory';
$language = 'de-DE';
$data = [
    'test.id1' => [
        'message' => 'Nachricht 1', // translated string
        'comment' => 'Comment for message 1', // is optional parameter for save extra metadata
    ],
    'test.id2' => [
        'message' => 'Nachricht 2',
    ],
    'test.id3' => [
        'message' => 'Nachricht 3',
    ],
];

$messageSource->write($category, $language, $data);
```

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Translator DB Message Storage is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
