<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Translator\Message\Db\DbSchemaManager;

abstract class AbstractDbSchemaManagerTest extends TestCase
{
    protected string $commentType = SchemaInterface::TYPE_TEXT;
    protected string $messageIdType = SchemaInterface::TYPE_TEXT;
    protected string $translationType = SchemaInterface::TYPE_TEXT;
    protected ConnectionInterface $db;
    private DbSchemaManager $dbSchemaManager;

    protected function setup(): void
    {
        parent::setUp();

        $this->dbSchemaManager = new DbSchemaManager($this->db);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->commentType, $this->db, $this->dbSchemaManager, $this->messageIdType, $this->translationType);
    }

    /**
     * @dataProvider tableNameProvider
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testEnsureTableAndEnsureNoTable(string $tableSourceMessage, string $tableMessage): void
    {
        $this->dbSchemaManager->ensureTables($tableSourceMessage, $tableMessage);

        $this->assertNotNull($this->db->getTableSchema($tableSourceMessage, true));
        $this->assertNotNull($this->db->getTableSchema($tableMessage, true));

        $this->dbSchemaManager->ensureNoTables($tableSourceMessage, $tableMessage);

        $this->assertNull($this->db->getTableSchema($tableSourceMessage, true));
        $this->assertNull($this->db->getTableSchema($tableMessage, true));
    }

    /**
     * @dataProvider tableNameProvider
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testEnsureTableExist(string $tableSourceMessage, string $tableMessage): void
    {
        $this->dbSchemaManager->ensureTables($tableSourceMessage, $tableMessage);

        $this->assertNotNull($this->db->getTableSchema($tableSourceMessage, true));
        $this->assertNotNull($this->db->getTableSchema($tableMessage, true));

        $this->dbSchemaManager->ensureTables($tableSourceMessage, $tableMessage);

        $this->assertNotNull($this->db->getTableSchema($tableSourceMessage, true));
        $this->assertNotNull($this->db->getTableSchema($tableMessage, true));

        $this->dbSchemaManager->ensureNoTables($tableSourceMessage, $tableMessage);

        $this->assertNull($this->db->getTableSchema($tableSourceMessage, true));
        $this->assertNull($this->db->getTableSchema($tableMessage, true));
    }

    /**
     * @dataProvider tableNameProvider
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testVerifyTableStructure(string $tableSourceMessage, string $tableMessage): void
    {
        $this->dbSchemaManager->ensureTables($tableSourceMessage, $tableMessage);

        $driverName = $this->db->getDriverName();
        $schema = $this->db->getSchema();
        $tableRawNameSourceMessage = $schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $schema->getRawTableName($tableMessage);

        $tableSchema = $this->db->getTableSchema($tableSourceMessage);

        $this->assertSame($tableRawNameSourceMessage, $tableSchema?->getName());
        $this->assertSame(['id'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'category', 'message_id', 'comment'], $tableSchema?->getColumnNames());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(SchemaInterface::TYPE_STRING, $tableSchema?->getColumn('category')->getType());
        $this->assertSame($this->messageIdType, $tableSchema?->getColumn('message_id')->getType());
        $this->assertSame($this->commentType, $tableSchema?->getColumn('comment')->getType());

        $tableSchema = $this->db->getTableSchema($tableMessage);

        $this->assertSame($tableRawNameMessage, $tableSchema?->getName());
        $this->assertSame(['id', 'locale'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'locale', 'translation'], $tableSchema?->getColumnNames());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(SchemaInterface::TYPE_STRING, $tableSchema?->getColumn('locale')->getType());
        $this->assertSame(16, $tableSchema?->getColumn('locale')->getSize());
        $this->assertSame($this->translationType, $tableSchema?->getColumn('translation')->getType());

        $foreignKeysExpected = [
            "FK_{$tableRawNameSourceMessage}_{$tableRawNameMessage}" => [
                0 => $tableRawNameSourceMessage,
                'id' => 'id',
            ],
        ];

        if ($driverName === 'oci' || $driverName === 'sqlite') {
            $foreignKeysExpected = [
                0 => [
                    0 => $tableRawNameSourceMessage,
                    'id' => 'id',
                ],
            ];
        }

        $this->assertSame($foreignKeysExpected, $tableSchema?->getForeignKeys());

        $this->dbSchemaManager->ensureNoTables($tableSourceMessage, $tableMessage);

        $this->assertNull($this->db->getTableSchema($tableSourceMessage, true));
        $this->assertNull($this->db->getTableSchema($tableMessage, true));
    }

    public static function tableNameProvider(): array
    {
        return [
            ['{{%yii_source_message}}', '{{%yii_message}}'],
            ['{{%custom_yii_source_message}}', '{{%custom_yii_message}}'],
        ];
    }
}
