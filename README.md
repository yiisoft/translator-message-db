<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Translator DB Message Storage</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/translator-message-db/v/stable.png)](https://packagist.org/packages/yiisoft/translator-message-db)
[![Total Downloads](https://poser.pugx.org/yiisoft/translator-message-db/downloads.png)](https://packagist.org/packages/yiisoft/translator-message-db)
[![codecov](https://codecov.io/gh/yiisoft/translator-message-db/branch/master/graph/badge.svg?token=H8PFGG5SWO)](https://codecov.io/gh/yiisoft/translator-message-db)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ftranslator-message-db%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/translator-message-db/master)
[![static analysis](https://github.com/yiisoft/translator-message-db/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/translator-message-db/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/translator-message-db/coverage.svg)](https://shepherd.dev/github/yiisoft/translator-message-db)

The package provides message storage backend based on DB to be used 
with [`yiisoft/translator`](https://github.com/yiisoft/translator) package.

## Supported databases

| Packages | PHP | Versions | CI-Actions |
|----------|-----|----------|------------|
|  [[db-mssql]](https://github.com/yiisoft/db-mssql) | **8.0 - 8.2** | **2017 - 2022** | [![mssql](https://github.com/yiisoft/translator-message-db/actions/workflows/mssql.yml/badge.svg)](https://github.com/yiisoft/translator-message-db/actions/workflows/mssql.yml) | |
|  [[db-mysql/mariadb]](https://github.com/yiisoft/db-mysql) | **8.0 - 8.2** |  **5.7-8.0**/**10.4-10.10**  | [![mysql](https://github.com/yiisoft/translator-message-db/actions/workflows/mysql.yml/badge.svg)](https://github.com/yiisoft/translator-message-db/actions/workflows/mysql.yml) |
| [[db-oracle]](https://github.com/yiisoft/db-oracle) | **8.0 - 8.2** |  **11C - 21C**  | [![oracle](https://github.com/yiisoft/translator-message-db/actions/workflows/oracle.yml/badge.svg)](https://github.com/yiisoft/translator-message-db/actions/workflows/oracle.yml) |
|  [[db-pgsql]](https://github.com/yiisoft/db-pgsql) | **8.0 - 8.2** | **9.0 - 15.0**  |  [![pgsql](https://github.com/yiisoft/translator-message-db/actions/workflows/pgsql.yml/badge.svg)](https://github.com/yiisoft/translator-message-db/actions/workflows/pgsql.yml)   |
| [[db-sqlite]](https://github.com/yiisoft/db-sqlite) | **8.0 - 8.2** |  **3:latest** | [![sqlite](https://github.com/yiisoft/translator-message-db/actions/workflows/sqlite.yml/badge.svg)](https://github.com/yiisoft/translator-message-db/actions/workflows/sqlite.yml) |

## Requirements

- PHP 8.0 or higher.
- `json` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/translator-message-db
```

## Create database connection

For more information see [yiisoft/db](https://github.com/yiisoft/db/tree/master/docs/en#create-connection).

## Database Preparing

Package provides two way for preparing database:

1. Raw SQL. You can use it with the migration package used in your application.

    - Ensure tables:
        - [MSSQL](/sql/sqlsrv-up.sql),
        - [MySQL / MariaDB](/sql/mysql-up.sql),
        - [Oracle](/sql/oci-up.sql),
        - [PostgreSQL](/sql/pgsql-up.sql)
        - [SQLite](/sql/sqlite-up.sql)
    
    - Ensure no tables:
        - [MSSQL](/sql/sqlsrv-down.sql),
        - [MySQL / MariaDB](/sql/mysql-down.sql),
        - [Oracle](/sql/oci-down.sql),
        - [PostgreSQL](/sql/pgsql-down.sql)
        - [SQLite](/sql/sqlite-down.sql)

2. `DbSchemaManager` for `ensureTables()`, `ensureNoTables()` methods for translator tables
(by default `{{%yii_source_message}}` and `{{%yii_message}}`).

## Configuration

### Quick start


In case you use [`yiisoft/config`](http://github.com/yiisoft/config), you will get configuration automatically.
If not, the following DI container configuration is necessary:

```php
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

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

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
