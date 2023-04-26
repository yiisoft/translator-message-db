<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Sqlite;

use Yiisoft\Translator\Message\Db\Migration;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\SqliteHelper;

/**
 * @group sqlite
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    protected function setUp(): void
    {
        $this->db = (new SqliteHelper())->createConnection();
        $this->db->setTablePrefix('sqlite3_');

        Migration::ensureTable($this->db);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        Migration::dropTable($this->db);

        parent::tearDown();
    }

    public function testPrefixTable(): void
    {
        $this->assertSame('sqlite3_source_message', $this->db->getSchema()->getRawTableName('{{%source_message}}'));
        $this->assertSame('sqlite3_message', $this->db->getSchema()->getRawTableName('{{%message}}'));
    }
}
