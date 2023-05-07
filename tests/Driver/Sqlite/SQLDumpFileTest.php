<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Sqlite;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractSQLDumpFileTest;
use Yiisoft\Translator\Message\Db\Tests\Support\SqliteFactory;

/**
 * @group Sqlite
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SQLDumpFileTest extends AbstractSQLDumpFileTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new SqliteFactory())->createConnection();

        parent::setUp();
    }
}
