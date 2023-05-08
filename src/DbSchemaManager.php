<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * Manages the translator tables schema in the database.
 */
final class DbSchemaManager
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    /**
     * Ensures that the translator tables exists in the database.
     *
     * @param string $table The name of the translator tables.
     * Defaults to '{{%yii_source_message}}', '{{%yii_message}}'.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function ensureTables(
        string $tableSourceMessage = '{{%yii_source_message}}',
        string $tableMessage = '{{%yii_message}}',
    ): void {
        $driverName = $this->db->getDriverName();
        $schema = $this->db->getSchema();
        $tableRawNameSourceMessage = $schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $schema->getRawTableName($tableMessage);

        if ($this->hasTable($tableSourceMessage) && $this->hasTable($tableMessage)) {
            return;
        }

        if ($driverName === 'sqlite') {
            $this->createSchemaSqlite(
                $schema,
                $tableSourceMessage,
                $tableRawNameSourceMessage,
                $tableMessage,
                $tableRawNameMessage,
            );

            return;
        }

        $this->createSchema(
            $schema,
            $driverName,
            $tableSourceMessage,
            $tableRawNameSourceMessage,
            $tableMessage,
            $tableRawNameMessage,
        );
    }

    /**
     * Ensures that the translator tables does not exist in the database.
     *
     * @param string $table The name of the translator tables.
     * Defaults to '{{%yii_source_message}}', '{{%yii_message}}'.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function ensureNoTables(
        string $tableSourceMessage = '{{%yii_source_message}}',
        string $tableMessage = '{{%yii_message}}',
    ): void {
        $driverName = $this->db->getDriverName();
        $schema = $this->db->getSchema();
        $tableRawNameSourceMessage = $schema->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $schema->getRawTableName($tableMessage);

        if ($this->hasTable($tableMessage)) {
            if ($driverName !== 'sqlite' && $schema->getTableForeignKeys($tableMessage, true) !== []) {
                // drop foreign key for table `yii_message`.
                $this->db
                    ->createCommand()
                    ->dropForeignKey(
                        $tableRawNameMessage,
                        "{{FK_{$tableRawNameSourceMessage}_{$tableRawNameMessage}}}",
                    )
                    ->execute();
            }

            // drop table `yii_message`.
            $this->db->createCommand()->dropTable($tableRawNameMessage)->execute();

            if ($driverName === 'oci') {
                // drop sequence for table `yii_message`.
                $this->db
                    ->createCommand()
                    ->setSql(
                        <<<SQL
                        DROP SEQUENCE {{{$tableRawNameMessage}_SEQ}}
                        SQL,
                    )
                    ->execute();
            }
        }

        if ($this->hasTable($tableSourceMessage)) {
            // drop table `yii_source_message`.
            $this->db->createCommand()->dropTable($tableRawNameSourceMessage)->execute();

            if ($driverName === 'oci') {
                // drop sequence for table `yii_source_message`.
                $this->db
                    ->createCommand()
                    ->setSql(
                        <<<SQL
                        DROP SEQUENCE {{{$tableRawNameSourceMessage}_SEQ}}
                        SQL,
                    )
                    ->execute();
            }
        }
    }

    /**
     * Add sequence and trigger for tables '{{%yii_source_message}}' and '{{%yii_message}}' for Oracle.
     *
     * @throws Exception
     * @throws Throwable
     */
    private function addSequenceAndTrigger(string $tableRawNameSourceMessage, string $tableRawNameMessage): void
    {
        // create a sequence for table `yii_source_message` and `yii_message`.
        $this->db
            ->createCommand()
            ->setSql(
                <<<SQL
                CREATE SEQUENCE {{{$tableRawNameSourceMessage}_SEQ}}
                START WITH 1
                INCREMENT BY 1
                NOMAXVALUE
                SQL,
            )
            ->execute();

        $this->db
            ->createCommand()
            ->setSql(
                <<<SQL
                CREATE SEQUENCE {{{$tableRawNameMessage}_SEQ}}
                START WITH 1
                INCREMENT BY 1
                NOMAXVALUE
                SQL,
            )
            ->execute();

        // create trigger for table `yii_source_message` and `yii_message`.
        $this->db
            ->createCommand()
            ->setSql(
                <<<SQL
                CREATE TRIGGER {{{$tableRawNameSourceMessage}_TRG}} BEFORE INSERT ON {{{$tableRawNameSourceMessage}}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
                IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{{$tableRawNameSourceMessage}_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
                END COLUMN_SEQUENCES;
                END;
                SQL,
            )
            ->execute();

        $this->db
            ->createCommand()
            ->setSql(
                <<<SQL
                CREATE TRIGGER {{{$tableRawNameMessage}_TRG}} BEFORE INSERT ON {{{$tableRawNameMessage}}} FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
                IF INSERTING AND :NEW."id" IS NULL THEN SELECT {{{$tableRawNameMessage}_SEQ}}.NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
                END COLUMN_SEQUENCES;
                END;
                SQL,
            )
            ->execute();
    }

    /**
     * Create index for tables '{{%yii_source_message}}' and '{{%yii_message}}'.
     *
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
        $this->db
            ->createCommand()
            ->createIndex($tableSourceMessage, "IDX_{$tableRawNameSourceMessage}_category", 'category')
            ->execute();
        $this->db
            ->createCommand()
            ->createIndex($tableMessage, "IDX_{$tableRawNameMessage}_locale", 'locale')
            ->execute();
    }

    /**
     * Create schema for tables '{{%yii_source_message}}' and '{{%yii_message}}'.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private function createSchema(
        SchemaInterface $schema,
        string $driverName,
        string $tableSourceMessage,
        string $tableRawNameSourceMessage,
        string $tableMessage,
        string $tableRawNameMessage
    ): void {
        $updateAction = 'RESTRICT';

        if ($driverName === 'sqlsrv') {
            // 'NO ACTION' is equal to 'RESTRICT' in `MSSQL`.
            $updateAction = 'NO ACTION';
        }

        if ($driverName === 'oci') {
            // Oracle doesn't support action for update.
            $updateAction = null;
        }

        // create table `yii_sorce_message`.
        $this->db
            ->createCommand()
            ->createTable(
                $tableSourceMessage,
                [
                    'id' => $schema->createColumn(SchemaInterface::TYPE_PK),
                    'category' => $schema->createColumn(SchemaInterface::TYPE_STRING),
                    'message_id' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                    'comment' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                ],
            )
            ->execute();

        // create table `yii_message`.
        $this->db
            ->createCommand()
            ->createTable(
                $tableMessage,
                [
                    'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                    'locale' => $schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                    'translation' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                ],
            )
            ->execute();

        // create primary key for table `yii_message`.
        $this->db
            ->createCommand()
            ->addPrimaryKey($tableMessage, "PK_{$tableRawNameMessage}_id_locale", ['id', 'locale'])
            ->execute();

        // create index for table `yii_source_message`.
        $this->createIndex($tableSourceMessage, $tableRawNameSourceMessage, $tableMessage, $tableRawNameMessage);

        if ($driverName === 'oci') {
            $this->addSequenceAndTrigger($tableRawNameSourceMessage, $tableRawNameMessage);
        }

        // Add foreign key for table `yii_message`.
        $this->db
            ->createCommand()
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
     * Create schema for tables in SQLite database.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private function createSchemaSqlite(
        SchemaInterface $schema,
        string $tableSourceMessage,
        string $tableRawNameSourceMessage,
        string $tableMessage,
        string $tableRawNameMessage
    ): void {
        // create table `yii_source_message`.
        $this->db
            ->createCommand()
            ->createTable(
                $tableSourceMessage,
                [
                    'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                    'category' => $schema->createColumn(SchemaInterface::TYPE_STRING),
                    'message_id' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                    'comment' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                    "CONSTRAINT [[PK_{$tableRawNameSourceMessage}]] PRIMARY KEY ([[id]])",
                ],
            )
            ->execute();

        // create table `yii_message`.
        $this->db
            ->createCommand()
            ->createTable(
                $tableMessage,
                [
                    'id' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull(),
                    'locale' => $schema->createColumn(SchemaInterface::TYPE_STRING, 16)->notNull(),
                    'translation' => $schema->createColumn(SchemaInterface::TYPE_TEXT),
                    'PRIMARY KEY (`id`, `locale`)',
                    "CONSTRAINT `FK_{$tableRawNameMessage}_{$tableRawNameSourceMessage}` FOREIGN KEY (`id`) REFERENCES `$tableRawNameSourceMessage` (`id`) ON DELETE CASCADE",
                ],
            )
            ->execute();

        $this->createIndex($tableSourceMessage, $tableRawNameSourceMessage, $tableMessage, $tableRawNameMessage);
    }

    /**
     * Checks if the given table exists in the database.
     *
     * @param string $table The name of the table to check.
     *
     * @return bool Whether the table exists or not.
     */
    private function hasTable(string $table): bool
    {
        return $this->db->getTableSchema($table, true) !== null;
    }
}
