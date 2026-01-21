<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Translator\Message\Db\DbSchemaManager;

abstract class AbstractDbSchemaManagerTest extends TestCase
{
    protected string $commentType = ColumnType::TEXT;
    protected string $messageIdType = ColumnType::TEXT;
    protected string $translationType = ColumnType::TEXT;
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
     */
    public function testVerifyTableStructure(string $tableSourceMessage, string $tableMessage): void
    {
        $this->dbSchemaManager->ensureTables($tableSourceMessage, $tableMessage);

        $driverName = $this->db->getDriverName();
        $quoter = $this->db->getQuoter();
        $tableRawNameSourceMessage = $quoter->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $quoter->getRawTableName($tableMessage);

        $tableSchema = $this->db->getTableSchema($tableSourceMessage);

        $this->assertSame($tableRawNameSourceMessage, $tableSchema?->getName());
        $this->assertSame(['id'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'category', 'message_id', 'comment'], $tableSchema?->getColumnNames());
        $this->assertSame(ColumnType::INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(ColumnType::STRING, $tableSchema?->getColumn('category')->getType());
        $this->assertSame($this->messageIdType, $tableSchema?->getColumn('message_id')->getType());
        $this->assertSame($this->commentType, $tableSchema?->getColumn('comment')->getType());

        $tableSchema = $this->db->getTableSchema($tableMessage);

        $this->assertSame($tableRawNameMessage, $tableSchema?->getName());
        $this->assertSame(['id', 'locale'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'locale', 'translation'], $tableSchema?->getColumnNames());
        $this->assertSame(ColumnType::INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(ColumnType::STRING, $tableSchema?->getColumn('locale')->getType());
        $this->assertSame(16, $tableSchema?->getColumn('locale')->getSize());
        $this->assertSame($this->translationType, $tableSchema?->getColumn('translation')->getType());

        $foreignKeys = $tableSchema?->getForeignKeys();

        $foreignKeyExpected = new ForeignKey(
            $foreignKeys[0]->name,
            ['id'],
            match ($driverName) {
                'sqlsrv' => 'dbo',
                'pgsql' => 'public',
                'oci' => 'YII',
                default => '',
            },
            $tableRawNameSourceMessage,
            ['id'],
            ReferentialAction::CASCADE,
            match ($driverName) {
                'sqlsrv', 'oci' => null,
                default => ReferentialAction::RESTRICT,
            },
        );

        $this->assertEquals([$foreignKeyExpected], $foreignKeys);

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
