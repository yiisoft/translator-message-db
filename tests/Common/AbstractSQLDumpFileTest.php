<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * @group Mssql
 */
abstract class AbstractSQLDumpFileTest extends TestCase
{
    protected ConnectionInterface $db;
    protected string $commentType = ColumnType::TEXT;
    protected string $messageIdType = ColumnType::TEXT;
    protected string $translationType = ColumnType::TEXT;
    private string $driverName = '';
    private string $tableSourceMessage = '{{%yii_source_message}}';
    private string $tableMessage = '{{%yii_message}}';

    protected function setup(): void
    {
        parent::setUp();

        $this->driverName = $this->db->getDriverName();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->db, $this->driverName);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testEnsureTableAndEnsureNoTable(): void
    {
        $this->loadFromSQLDumpFile(dirname(__DIR__, 2) . "/sql/$this->driverName-up.sql");

        $this->assertNotNull($this->db->getTableSchema($this->tableSourceMessage, true));
        $this->assertNotNull($this->db->getTableSchema($this->tableMessage, true));

        $this->loadFromSQLDumpFile(dirname(__DIR__, 2) . "/sql/$this->driverName-down.sql");

        $this->assertNull($this->db->getTableSchema($this->tableSourceMessage, true));
        $this->assertNull($this->db->getTableSchema($this->tableMessage, true));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testVerifyTableStructure(): void
    {
        $this->loadFromSQLDumpFile(dirname(__DIR__, 2) . "/sql/$this->driverName-up.sql");

        $tableSchema = $this->db->getTableSchema($this->tableSourceMessage);

        $this->assertSame('yii_source_message', $tableSchema?->getName());
        $this->assertSame(['id'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'category', 'message_id', 'comment'], $tableSchema?->getColumnNames());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(SchemaInterface::TYPE_STRING, $tableSchema?->getColumn('category')->getType());
        $this->assertSame($this->messageIdType, $tableSchema?->getColumn('message_id')->getType());
        $this->assertSame($this->commentType, $tableSchema?->getColumn('comment')->getType());

        $tableSchema = $this->db->getTableSchema($this->tableMessage);

        $this->assertSame('yii_message', $tableSchema?->getName());
        $this->assertSame(['id', 'locale'], $tableSchema?->getPrimaryKey());
        $this->assertSame(['id', 'locale', 'translation'], $tableSchema?->getColumnNames());
        $this->assertSame(SchemaInterface::TYPE_INTEGER, $tableSchema?->getColumn('id')->getType());
        $this->assertSame(SchemaInterface::TYPE_STRING, $tableSchema?->getColumn('locale')->getType());
        $this->assertSame(16, $tableSchema?->getColumn('locale')->getSize());
        $this->assertSame($this->translationType, $tableSchema?->getColumn('translation')->getType());

        $foreignKeysExpected = [
            'FK_yii_source_message_yii_message' => [
                0 => 'yii_source_message',
                'id' => 'id',
            ],
        ];

        if ($this->driverName === 'oci' || $this->driverName === 'sqlite') {
            $foreignKeysExpected = [
                0 => [
                    0 => 'yii_source_message',
                    'id' => 'id',
                ],
            ];
        }

        $this->assertSame($foreignKeysExpected, $tableSchema?->getForeignKeys());

        $this->loadFromSQLDumpFile(dirname(__DIR__, 2) . "/sql/$this->driverName-down.sql");

        $this->assertNull($this->db->getTableSchema($this->tableSourceMessage, true));
        $this->assertNull($this->db->getTableSchema($this->tableMessage, true));
    }

    /**
     * Loads the fixture into the database.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    private function loadFromSQLDumpFile(string $fixture): void
    {
        $this->db->open();

        if ($this->driverName === 'oci') {
            [$creates] = explode('/* STATEMENTS */', file_get_contents($fixture), 1);
            if (!str_contains($creates, '/* TRIGGERS */')) {
                $lines = explode(';', $creates);
            } else {
                [$statements, $triggers] = explode('/* TRIGGERS */', $creates, 2);
                $lines = array_merge(
                    explode(';', $statements),
                    explode('/', $triggers),
                );
            }
        } else {
            $lines = explode(';', file_get_contents($fixture));
        }

        foreach ($lines as $line) {
            $this->db->createCommand(trim($line))->execute();
        }
    }
}
