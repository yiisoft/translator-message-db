<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Mysql;

use Yiisoft\Translator\Message\Db\Migration;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\MysqlHelper;

/**
 * @group Mysql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    protected function setUp(): void
    {
        $this->db = (new MysqlHelper())->createConnection();
        $this->db->setTablePrefix('mysql_');

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
        $this->assertSame('mysql_source_message', $this->db->getSchema()->getRawTableName('{{%source_message}}'));
        $this->assertSame('mysql_message', $this->db->getSchema()->getRawTableName('{{%message}}'));
    }
}
