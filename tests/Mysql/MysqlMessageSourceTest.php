<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Mysql;

use Yiisoft\Translator\Message\Db\Tests\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\MysqlHelper;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @group mysql
 */
final class MysqlMessageSourceTest extends AbstractMessageSourceTest
{
    use MysqlHelper;
}
