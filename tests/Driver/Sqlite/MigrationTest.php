<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Sqlite;

use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMigrationTest;
use Yiisoft\Translator\Message\Db\Tests\Support\SqliteHelper;

/**
 * @group Sqlite
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MigrationTest extends AbstractMigrationTest
{
    protected function setUp(): void
    {
        // create connection dbms-specific
        $this->db = (new SqliteHelper())->createConnection();

        // set table prefix
        $this->db->setTablePrefix('sqlite3_');

        parent::setUp();
    }
}
