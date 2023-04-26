<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class Migration
{
    public static function ensureTable(ConnectionInterface $db): void
    {
        $command = $db->createCommand();
        $driverName = $db->getDriverName();
        $schema = $db->getSchema();

        if (
            $schema->getTableSchema('{{%source_message}}', true) !== null &&
            $schema->getTableSchema('{{%message}}', true) !== null
        ) {
            return;
        }

        if ($driverName === 'sqlite') {
            self::ensureTableSqlite($command, $schema);

            return;
        }

        self::ensureTableNotSqlite($command, $driverName, $schema);
    }

    public static function dropTable(ConnectionInterface $db): void
    {
        $command = $db->createCommand();
        $driverName = $db->getDriverName();
        $schema = $db->getSchema();

        // drop sequence for table `source_message` and `message`.
        if ($db->getSchema()->getTableSchema('{{%message}}', true) !== null) {
            // drop foreign key for table `message`.
            if ($driverName !== 'sqlite' && $schema->getTableForeignKeys('{{FK_message_source_message}}', true)) {
                $command->dropForeignKey('{{%message}}', '{{FK_message_source_message}}')->execute();
            }

            // drop table `message`.
            $command->dropTable('{{%message}}')->execute();

            if ($driverName === 'oci') {
                $command->setSql(
                    <<<SQL
                    DROP SEQUENCE {{%message_SEQ}}
                    SQL,
                )->execute();
            }
        }

        if ($db->getSchema()->getTableSchema('{{%source_message}}', true) !== null) {
            // drop table `source_message`.
            $command->dropTable('{{%source_message}}')->execute();

            if ($driverName === 'oci') {
                $command->setSql(
                    <<<SQL
                    DROP SEQUENCE {{%source_message_SEQ}}
                    SQL,
                )->execute();
            }
        }
    }

    private static function addForeingKeyMigration(CommandInterface $command, string|null $update = null): void
    {
        $command->addForeignKey(
            '{{%message}}',
            'FK_message_source_message',
            ['id'],
            '{{%source_message}}',
            ['id'],
            'CASCADE',
            $update
        )->execute();
    }

    private static function addPrimaryKey(CommandInterface $command): void
    {
        $command->addPrimaryKey('{{%message}}', 'PK_message_id_locale', ['id', 'locale'])->execute();
    }

    private static function addSequenceAndTrigger(CommandInterface $command): void
    {
        // create sequence for table `source_message` and `message`.
        $command->setSql(
            <<<SQL
            CREATE SEQUENCE {{%source_message_SEQ}}
            START WITH 1
            INCREMENT BY 1
            NOMAXVALUE
            SQL,
        )->execute();

        $command->setSql(
            <<<SQL
            CREATE SEQUENCE {{%message_SEQ}}
            START WITH 1
            INCREMENT BY 1
            NOMAXVALUE
            SQL,
        )->execute();

        // create trigger for table `source_message` and `message`.
        $command->setSql(
            <<<SQL
            CREATE TRIGGER {{%source_message_TRG}} BEFORE INSERT ON {{%source_message}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
            IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{%source_message_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
            END COLUMN_SEQUENCES;
            END;
            SQL,
        )->execute();

        $command->setSql(
            <<<SQL
            CREATE TRIGGER {{%message_TRG}} BEFORE INSERT ON {{%message}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
            IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{%message_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
            END COLUMN_SEQUENCES;
            END;
            SQL,
        )->execute();
    }

    private static function createIndexForMigration(CommandInterface $command): void
    {
        $command->createIndex('{{%source_message}}', 'IDX_source_message_category', 'category')->execute();
        $command->createIndex('{{%message}}', 'IDX_message_locale', 'locale')->execute();
    }

    private static function createTable(CommandInterface $command, SchemaInterface $schema): void
    {
        // create table `source_message`.
        $command->createTable(
            '{{%source_message}}',
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_PK),
                'category' => $schema->createColumn(SchemaInterface::TYPE_STRING),
                'message_id' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'comment' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
            ],
        )->execute();

        // create table `message`.
        $command->createTable(
            '{{%message}}',
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'locale' => $schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                'translation' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
            ],
        )->execute();
    }

    private static function ensureTableNotSqlite(
        CommandInterface $command,
        string $driverName,
        SchemaInterface $schema
    ): void {
        $updateAction = 'RESTRICT';

        if ($driverName === 'sqlsrv') {
            // 'NO ACTION' is equivalent to 'RESTRICT' in MSSQL
            $updateAction = 'NO ACTION';
        }

        if ($driverName === 'oci') {
            // Oracle does not support action for update.
            $updateAction = null;
        }

        self::createTable($command, $schema);

        // create index for table `source_message` and `message`.
        self::createIndexForMigration($command);

        if ($driverName === 'oci') {
            // create sequence and trigger for table `source_message` and `message`.
            self::addSequenceAndTrigger($command);
        }

        // create primary key for table `source_message` and `message`.
        self::addPrimaryKey($command);

        // add foreign key for table `message`.
        self::addForeingKeyMigration($command, $updateAction);
    }

    private static function ensureTableSqlite(CommandInterface $command, SchemaInterface $schema): void
    {
        // create table `source_message`.
        $command->createTable(
            '{{%source_message}}',
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'category' => $schema->createColumn(SchemaInterface::TYPE_STRING),
                'message_id' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'comment' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'CONSTRAINT [[PK_message]] PRIMARY KEY ([[id]])',
            ],
        )->execute();

        // create table `message`.
        $command->createTable(
            '{{%message}}',
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'locale' => $schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                'translation' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'PRIMARY KEY (`id`, `locale`)',
                'CONSTRAINT `FK_message_source_message` FOREIGN KEY (`id`) REFERENCES `source_message` (`id`) ON DELETE CASCADE',
            ],
        )->execute();

        // create index for table `source_message` and `message`.
        self::createIndexForMigration($command);
    }
}
