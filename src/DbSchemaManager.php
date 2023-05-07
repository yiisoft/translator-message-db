<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\SchemaInterface;

final class DbSchemaManager
{
    private CommandInterface $command;
    private string $driverName;
    private SchemaInterface $schema;

    public function __construct(private ConnectionInterface $db)
    {
        $this->command = $db->createCommand();
        $this->driverName = $db->getDriverName();
        $this->schema = $db->getSchema();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function ensureTables(
        string $tableSourceMessage = '{{%yii_source_message}}',
        string $tableMessage = '{{%yii_message}}',
    ): void {
        $tableRawNameSourceMessage = $this->schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $this->schema->getRawTableName($tableMessage);

        if ($this->hasTable($tableSourceMessage) && $this->hasTable($tableMessage)) {
            return;
        }

        if ($this->driverName === 'sqlite') {
            $this->createSchemaSqlite(
                $tableSourceMessage,
                $tableRawNameSourceMessage,
                $tableMessage,
                $tableRawNameMessage,
            );

            return;
        }

        $this->createSchema($tableSourceMessage, $tableRawNameSourceMessage, $tableMessage, $tableRawNameMessage);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function ensureNoTables(
        string $tableSourceMessage = '{{%yii_source_message}}',
        string $tableMessage = '{{%yii_message}}',
    ): void {
        $tableRawNameSourceMessage = $this->schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $this->schema->getRawTableName($tableMessage);

        // drop sequence for table `source_message` and `message`.
        if ($this->hasTable($tableMessage)) {
            // drop foreign key for table `message`.
            if ($this->driverName !== 'sqlite' && $this->schema->getTableForeignKeys($tableMessage, true) !== []) {
                $this->command->dropForeignKey(
                    $tableRawNameMessage,
                    "{{FK_{$tableRawNameSourceMessage}_{$tableRawNameMessage}}}",
                )->execute();
            }

            // drop table `message`.
            $this->command->dropTable($tableRawNameMessage)->execute();

            if ($this->driverName === 'oci') {
                $this->command->setSql(
                    <<<SQL
                    DROP SEQUENCE {{{$tableRawNameMessage}_SEQ}}
                    SQL,
                )->execute();
            }
        }

        if ($this->hasTable($tableSourceMessage)) {
            // drop table `source_message`.
            $this->command->dropTable($tableRawNameSourceMessage)->execute();

            if ($this->driverName === 'oci') {
                $this->command->setSql(
                    <<<SQL
                    DROP SEQUENCE {{{$tableRawNameSourceMessage}_SEQ}}
                    SQL,
                )->execute();
            }
        }
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    private function addSequenceAndTrigger(string $tableRawNameSourceMessage, string $tableRawNameMessage): void
    {
        // create a sequence for table `source_message` and `message`.
        $this->command->setSql(
            <<<SQL
            CREATE SEQUENCE {{{$tableRawNameSourceMessage}_SEQ}}
            START WITH 1
            INCREMENT BY 1
            NOMAXVALUE
            SQL,
        )->execute();

        $this->command->setSql(
            <<<SQL
            CREATE SEQUENCE {{{$tableRawNameMessage}_SEQ}}
            START WITH 1
            INCREMENT BY 1
            NOMAXVALUE
            SQL,
        )->execute();

        // create trigger for table `source_message` and `message`.
        $this->command->setSql(
            <<<SQL
            CREATE TRIGGER {{{$tableRawNameSourceMessage}_TRG}} BEFORE INSERT ON {{{$tableRawNameSourceMessage}}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
            IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{{$tableRawNameSourceMessage}_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
            END COLUMN_SEQUENCES;
            END;
            SQL,
        )->execute();

        $this->command->setSql(
            <<<SQL
            CREATE TRIGGER {{{$tableRawNameMessage}_TRG}} BEFORE INSERT ON {{{$tableRawNameMessage}}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
            IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{{$tableRawNameMessage}_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
            END COLUMN_SEQUENCES;
            END;
            SQL,
        )->execute();
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws Throwable
     */
    private function createIndex(
        string $tableSourceMessage,
        string $tableRawNameSourceMessage,
        string $tableMessage,
        string $tableRawNameMessage
    ): void {
        $this->command
            ->createIndex($tableSourceMessage, "IDX_{$tableRawNameSourceMessage}_category", 'category')
            ->execute();
        $this->command
            ->createIndex($tableMessage, "IDX_{$tableRawNameMessage}_locale", 'locale')
            ->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private function createSchema(
        string $tableSourceMessage,
        string $tableRawNameSourceMessage,
        string $tableMessage,
        string $tableRawNameMessage
    ): void {
        $updateAction = 'RESTRICT';

        if ($this->driverName === 'sqlsrv') {
            // 'NO ACTION' is equal to 'RESTRICT' in MSSQL
            $updateAction = 'NO ACTION';
        }

        if ($this->driverName === 'oci') {
            // Oracle doesn't support action for update.
            $updateAction = null;
        }

        // create table `source_message`.
        $this->command->createTable(
            $tableSourceMessage,
            [
                'id' => $this->schema->createColumn(SchemaInterface::TYPE_PK),
                'category' => $this->schema->createColumn(SchemaInterface::TYPE_STRING),
                'message_id' => $this->schema->createColumn(SchemaInterface::TYPE_TEXT),
                'comment' => $this->schema->createColumn(SchemaInterface::TYPE_TEXT),
            ],
        )->execute();

        // create table `message`.
        $this->command->createTable(
            $tableMessage,
            [
                'id' => $this->schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'locale' => $this->schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                'translation' => $this->schema->createColumn(SchemaInterface::TYPE_TEXT),
            ],
        )->execute();

        // create primary key for table `source_message` and `message`.
        $this->command
            ->addPrimaryKey($tableMessage, "PK_{$tableRawNameMessage}_id_locale", ['id', 'locale'])
            ->execute();

        // create index for table `source_message` and `message`.
        $this->createIndex($tableSourceMessage, $tableRawNameSourceMessage, $tableMessage, $tableRawNameMessage);

        if ($this->driverName === 'oci') {
            // create sequence and trigger for table `source_message` and `message`.
            $this->addSequenceAndTrigger($tableRawNameSourceMessage, $tableRawNameMessage);
        }

        // add foreign key for table `message`.
        $this->command
            ->addForeignKey(
                $tableMessage,
                "FK_{$tableRawNameSourceMessage}_{$tableRawNameMessage}",
                ['id'],
                $tableRawNameSourceMessage,
                ['id'],
                'CASCADE',
                $updateAction,
            )->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private function createSchemaSqlite(
        string $tableSourceMessage,
        string $tableRawNameSourceMessage,
        string $tableMessage,
        string $tableRawNameMessage
    ): void {
        // create table `source_message`.
        $this->command->createTable(
            $tableSourceMessage,
            [
                'id' => $this->schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'category' => $this->schema->createColumn(SchemaInterface::TYPE_STRING),
                'message_id' => $this->schema->createColumn(SchemaInterface::TYPE_TEXT),
                'comment' => $this->schema->createColumn(SchemaInterface::TYPE_TEXT),
                "CONSTRAINT [[PK_{$tableRawNameSourceMessage}]] PRIMARY KEY ([[id]])",
            ],
        )->execute();

        // create table `message`.
        $this->command->createTable(
            $tableMessage,
            [
                'id' => $this->schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'locale' => $this->schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                'translation' => $this->schema->createColumn(SchemaInterface::TYPE_TEXT),
                'PRIMARY KEY (`id`, `locale`)',
                "CONSTRAINT `FK_{$tableRawNameMessage}_{$tableRawNameSourceMessage}` FOREIGN KEY (`id`) REFERENCES `$tableRawNameSourceMessage` (`id`) ON DELETE CASCADE",
            ],
        )->execute();

        // create index for table `source_message` and `message`.
        $this->createIndex($tableSourceMessage, $tableRawNameSourceMessage, $tableMessage, $tableRawNameMessage);
    }

    private function hasTable(string $table): bool
    {
        return $this->db->getTableSchema($table, true) !== null;
    }
}
