<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Sqlite;

use Yiisoft\Translator\Message\Db\Tests\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\SqliteHelper;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @group sqlite
 */
final class SqliteMessageSourceTest extends AbstractMessageSourceTest
{
    use SqliteHelper;
}
