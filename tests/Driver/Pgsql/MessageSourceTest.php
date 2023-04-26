<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Pgsql;

use Yiisoft\Translator\Message\Db\Migration;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\PgsqlHelper;

/**
 * @group Pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    protected function setUp(): void
    {
        $this->db = (new PgsqlHelper())->createConnection();
        $this->db->setTablePrefix('pgsql_');

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
        $this->assertSame('pgsql_source_message', $this->db->getSchema()->getRawTableName('{{%source_message}}'));
        $this->assertSame('pgsql_message', $this->db->getSchema()->getRawTableName('{{%message}}'));
    }
}
