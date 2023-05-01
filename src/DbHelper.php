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

use function in_array;
use function sprintf;

final class DbHelper
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public static function ensureTables(
        ConnectionInterface $db,
        string $tableSourceMessage = '{{%source_message}}',
        string $tableMessage = '{{%message}}',
    ): void {
        $command = $db->createCommand();
        $driverName = $db->getDriverName();
        $schema = $db->getSchema();
        $tableRawNameSourceMessage = $schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $schema->getRawTableName($tableMessage);

        self::validateSupportedDatabase($driverName);

        if (
            $schema->getTableSchema($tableSourceMessage, true) !== null &&
            $schema->getTableSchema($tableMessage, true) !== null
        ) {
            return;
        }

        if ($driverName === 'sqlite') {
            self::createSchemaSqlite(
                $command,
                $schema,
                $tableRawNameSourceMessage,
                $tableRawNameMessage
            );

            return;
        }

        self::createSchema(
            $command,
            $driverName,
            $schema,
            $tableRawNameSourceMessage,
            $tableRawNameMessage
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public static function dropTables(
        ConnectionInterface $db,
        string $tableSourceMessage = '{{%source_message}}',
        string $tableMessage = '{{%message}}',
    ): void {
        $command = $db->createCommand();
        $driverName = $db->getDriverName();
        $schema = $db->getSchema();
        $tableRawNameSourceMessage = $schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $schema->getRawTableName($tableMessage);

        self::validateSupportedDatabase($driverName);

        // drop sequence for table `source_message` and `message`.
        if ($db->getTableSchema($tableMessage, true) !== null) {
            // drop foreign key for table `message`.
            if ($driverName !== 'sqlite' && $schema->getTableForeignKeys($tableMessage, true) !== []) {
                $command->dropForeignKey(
                    $tableMessage,
                    "{{FK_{$tableRawNameSourceMessage}_{$tableRawNameMessage}}}",
                )->execute();
            }

            // drop table `message`.
            $command->dropTable($tableMessage)->execute();

            if ($driverName === 'oci') {
                $command->setSql(
                    <<<SQL
                    DROP SEQUENCE {{{$tableRawNameMessage}_SEQ}}
                    SQL,
                )->execute();
            }
        }

        if ($db->getTableSchema($tableSourceMessage, true) !== null) {
            // drop table `source_message`.
            $command->dropTable($tableSourceMessage)->execute();

            if ($driverName === 'oci') {
                $command->setSql(
                    <<<SQL
                    DROP SEQUENCE {{{$tableRawNameSourceMessage}_SEQ}}
                    SQL,
                )->execute();
            }
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    private static function addForeignKeyMigration(
        CommandInterface $command,
        string $tableRawNameSourceMessage,
        string $tableRawNameMessage,
        string|null $update = null
    ): void {
        $command->addForeignKey(
            $tableRawNameMessage,
            "FK_{$tableRawNameSourceMessage}_{$tableRawNameMessage}",
            ['id'],
            $tableRawNameSourceMessage,
            ['id'],
            'CASCADE',
            $update
        )->execute();
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    private static function addPrimaryKey(CommandInterface $command, string $tableRawNameMessage): void
    {
        $command->addPrimaryKey(
            $tableRawNameMessage,
            "PK_{$tableRawNameMessage}_id_locale",
            ['id', 'locale']
        )->execute();
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    private static function addSequenceAndTrigger(
        CommandInterface $command,
        string $tableRawNameSourceMessage,
        string $tableRawNameMessage
    ): void {
        // create a sequence for table `source_message` and `message`.
        $command->setSql(
            <<<SQL
            CREATE SEQUENCE {{{$tableRawNameSourceMessage}_SEQ}}
            START WITH 1
            INCREMENT BY 1
            NOMAXVALUE
            SQL,
        )->execute();

        $command->setSql(
            <<<SQL
            CREATE SEQUENCE {{{$tableRawNameMessage}_SEQ}}
            START WITH 1
            INCREMENT BY 1
            NOMAXVALUE
            SQL,
        )->execute();

        // create trigger for table `source_message` and `message`.
        $command->setSql(
            <<<SQL
            CREATE TRIGGER {{{$tableRawNameSourceMessage}_TRG}} BEFORE INSERT ON {{{$tableRawNameSourceMessage}}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
            IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{{$tableRawNameSourceMessage}_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
            END COLUMN_SEQUENCES;
            END;
            SQL,
        )->execute();

        $command->setSql(
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
    private static function createIndexForMigration(
        CommandInterface $command,
        string $tableRawNameSourceMessage,
        string $tableRawNameMessage
    ): void {
        $command->createIndex(
            $tableRawNameSourceMessage,
            "IDX_{$tableRawNameSourceMessage}_category",
            'category',
        )->execute();
        $command->createIndex(
            $tableRawNameMessage,
            "IDX_{$tableRawNameMessage}_locale",
            'locale'
        )->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private static function createTable(
        CommandInterface $command,
        SchemaInterface $schema,
        string $tableRawNameSourceMessage,
        string $tableRawNameMessage
    ): void {
        // create table `source_message`.
        $command->createTable(
            $tableRawNameSourceMessage,
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_PK),
                'category' => $schema->createColumn(SchemaInterface::TYPE_STRING),
                'message_id' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'comment' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
            ],
        )->execute();

        // create table `message`.
        $command->createTable(
            $tableRawNameMessage,
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'locale' => $schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                'translation' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
            ],
        )->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private static function createSchema(
        CommandInterface $command,
        string $driverName,
        SchemaInterface $schema,
        string $tableRawNameSourceMessage,
        string $tableRawNameMessage
    ): void {
        $updateAction = 'RESTRICT';

        if ($driverName === 'sqlsrv') {
            // 'NO ACTION' is equal to 'RESTRICT' in MSSQL
            $updateAction = 'NO ACTION';
        }

        if ($driverName === 'oci') {
            // Oracle doesn't support action for update.
            $updateAction = null;
        }

        self::createTable($command, $schema, $tableRawNameSourceMessage, $tableRawNameMessage);

        // create primary key for table `source_message` and `message`.
        self::addPrimaryKey($command, $tableRawNameMessage);

        // create index for table `source_message` and `message`.
        self::createIndexForMigration($command, $tableRawNameSourceMessage, $tableRawNameMessage);

        if ($driverName === 'oci') {
            // create sequence and trigger for table `source_message` and `message`.
            self::addSequenceAndTrigger($command, $tableRawNameSourceMessage, $tableRawNameMessage);
        }

        // add foreign key for table `message`.
        self::addForeignKeyMigration($command, $tableRawNameSourceMessage, $tableRawNameMessage, $updateAction);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private static function createSchemaSqlite(
        CommandInterface $command,
        SchemaInterface $schema,
        string $tableRawNameSourceMessage,
        string $tableRawNameMessage
    ): void {
        // create table `source_message`.
        $command->createTable(
            $tableRawNameSourceMessage,
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'category' => $schema->createColumn(SchemaInterface::TYPE_STRING),
                'message_id' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'comment' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                "CONSTRAINT [[PK_{$tableRawNameSourceMessage}]] PRIMARY KEY ([[id]])",
            ],
        )->execute();

        // create table `message`.
        $command->createTable(
            $tableRawNameMessage,
            [
                'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                'locale' => $schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                'translation' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                'PRIMARY KEY (`id`, `locale`)',
                "CONSTRAINT `FK_{$tableRawNameMessage}_{$tableRawNameSourceMessage}` FOREIGN KEY (`id`) REFERENCES `$tableRawNameSourceMessage` (`id`) ON DELETE CASCADE",
            ],
        )->execute();

        // create index for table `source_message` and `message`.
        self::createIndexForMigration($command, $tableRawNameSourceMessage, $tableRawNameMessage);
    }

    private static function validateSupportedDatabase(string $driverName): void
    {
        if (!in_array($driverName, ['mysql', 'oci', 'pgsql', 'sqlite', 'sqlsrv'], true)) {
            throw new NotSupportedException(
                sprintf(
                    'Database driver `%s` is not supported.',
                    $driverName,
                ),
            );
        }
    }
}
