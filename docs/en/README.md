# Getting started

## Requirements

- The minimum version of PHP required by this package is `8.0`.
- `json` PHP extension.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/translator-message-db --prefer-dist
```

## Configuration

### Quick start

**Step 1.** You need a configured database connection for create migration and use in `yiisoft/translator-message-db` package,
for more information see [yiisoft/db](https://github.com/yiisoft/db/tree/master/docs/en#create-connection).

**Step 2.** Create migration for `source_message` and `message` tables:

For default tables:

```php
DbHelper::ensureTable($db);
```

For custom tables:

```php
DbHelper::ensureTables($db, '{{%custom_source_message_table}}', '{{%custom_message_table}}');
```

For dropping tables:

```php
DbHelper::dropTables($db);
```

For custom tables:

```php
DbHelper::dropTable($db, '{{%custom_source_message_table}}', '{{%custom_message_table}}');
```

**Step 3.** In case you use [`yiisoft/config`](http://github.com/yiisoft/config), you will get configuration automatically. If not, the following DI container configuration is necessary:

> Note: Additionally you can import the `RAW SQL` directly to create the tables.
>
>- [schema-mssql](/docs/en/migration/schema-mssql.sql).
>- [schema-mysql](/docs/en/migration/schema-mysql.sql).
>- [schema-oracle](/docs/en/migration/schema-oci.sql).
>- [schema-pgsql](/docs/en/migration/schema-pgsql.sql).
>- [schema-sqlite](/docs/en/migration/schema-sqlite.sql).

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
