<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Driver\Oracle;

use Yiisoft\Translator\Message\Db\Migration;
use Yiisoft\Translator\Message\Db\Tests\Common\AbstractMessageSourceTest;
use Yiisoft\Translator\Message\Db\Tests\Support\OracleHelper;

/**
 * @group Oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MessageSourceTest extends AbstractMessageSourceTest
{
    protected function setUp(): void
    {
        $this->db = (new OracleHelper())->createConnection();
        $this->db->setTablePrefix('oci_');

        Migration::ensureTable($this->db);

        parent::setup();
    }

    protected function tearDown(): void
    {
        Migration::dropTable($this->db);

        parent::tearDown();
    }

    public function testPrefixTable(): void
    {
        $this->assertSame('oci_source_message', $this->db->getSchema()->getRawTableName('{{%source_message}}'));
        $this->assertSame('oci_message', $this->db->getSchema()->getRawTableName('{{%message}}'));
    }
}
