<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Mssql;

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
    protected function setUp(): void
    {
        $this->db = (new MssqlHelper())->createConnection();
        $this->db->setTablePrefix('mssql_');

        Migration::dropTable($this->db);
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
        $this->assertSame('mssql_source_message', $this->db->getSchema()->getRawTableName('{{%source_message}}'));
        $this->assertSame('mssql_message', $this->db->getSchema()->getRawTableName('{{%message}}'));
    }
}
