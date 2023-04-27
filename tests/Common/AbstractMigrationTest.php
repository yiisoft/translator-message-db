<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Translator\Message\Db\Migration;

abstract class AbstractMigrationTest extends TestCase
{
    protected string $commentType = SchemaInterface::TYPE_TEXT;
    protected string $messageIdType = SchemaInterface::TYPE_TEXT;
    protected string $translationType = SchemaInterface::TYPE_TEXT;

    protected ConnectionInterface $db;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDropTable(): void
    {
        Migration::ensureTable($this->db);

        $this->assertNotNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNotNull($this->db->getTableSchema('{{%message}}', true));

        Migration::dropTable($this->db);

        $this->assertNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNull($this->db->getTableSchema('{{%message}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testEnsureTable(): void
    {
        Migration::ensureTable($this->db);

        $this->assertNotNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNotNull($this->db->getTableSchema('{{%message}}', true));

        Migration::dropTable($this->db);

        $this->assertNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNull($this->db->getTableSchema('{{%message}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testEnsureTableExist(): void
    {
        Migration::ensureTable($this->db);

        $this->assertNotNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNotNull($this->db->getTableSchema('{{%message}}', true));

        Migration::ensureTable($this->db);

        $this->assertNotNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNotNull($this->db->getTableSchema('{{%message}}', true));

        Migration::dropTable($this->db);

        $this->assertNull($this->db->getTableSchema('{{%source_message}}', true));
        $this->assertNull($this->db->getTableSchema('{{%message}}', true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testVerifyTableStructure(): void
    {
        Migration::ensureTable($this->db, '{{%test_source_message}}', '{{%test_message}}');

        $prefix = $this->db->getTablePrefix();
        $driverName = $this->db->getDriverName();
        $tableSchema = $this->db->getTableSchema('{{%test_source_message}}');

        $this->assertSame($prefix . 'test_source_message', $tableSchema?->getName());
        $this->assertSame(['id'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'category', 'message_id', 'comment'], $tableSchema?->getColumnNames());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(SchemaInterface::TYPE_STRING, $tableSchema?->getColumn('category')->getType());
        $this->assertSame($this->messageIdType, $tableSchema?->getColumn('message_id')->getType());
        $this->assertSame($this->commentType, $tableSchema?->getColumn('comment')->getType());

        $tableSchema = $this->db->getTableSchema('{{%test_message}}');

        $this->assertSame($prefix . 'test_message', $tableSchema?->getName());
        $this->assertSame(['id', 'locale'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'locale', 'translation'], $tableSchema?->getColumnNames());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(SchemaInterface::TYPE_STRING, $tableSchema?->getColumn('locale')->getType());
        $this->assertSame(16, $tableSchema?->getColumn('locale')->getSize());
        $this->assertSame($this->translationType, $tableSchema?->getColumn('translation')->getType());

        $foreignKeysExpected = [
            "FK_{$prefix}test_source_message_{$prefix}test_message" => [
                0 => "{$prefix}test_source_message",
                'id' => 'id',
            ],
        ];

        if ($driverName === 'oci' || $driverName === 'sqlite') {
            $foreignKeysExpected = [
                0 => [
                    0 => "{$prefix}test_source_message",
                    'id' => 'id',
                ],
            ];
        }

        $this->assertSame($foreignKeysExpected, $tableSchema?->getForeignKeys());

        Migration::dropTable($this->db, '{{%test_source_message}}', '{{%test_message}}');

        $this->assertNull($this->db->getTableSchema('{{%test_source_message}}', true));
        $this->assertNull($this->db->getTableSchema('{{%test_message}}', true));
    }
}
