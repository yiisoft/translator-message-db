<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Sqlite;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\SqliteFactory;

/**
 * @group sqlite
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new SqliteFactory())->createConnection();

        parent::setUp();
    }
}
