<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Mssql;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Translator\Message\Db\Migration;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\MssqlHelper;

/**
 * @group Mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new MssqlHelper())->createConnection();

        Migration::ensureTable($this->db);

        parent::setUp();
    }
}
