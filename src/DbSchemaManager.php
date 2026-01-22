<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;

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
     * @param string $tableSourceMessage The name of the source messages table, `{{%yii_source_message}}` by default.
     * @param string $tableMessage The name of the locale messages table, `{{%yii_message}}` by default.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function ensureTables(
        string $tableSourceMessage = '{{%yii_source_message}}',
        string $tableMessage = '{{%yii_message}}',
    ): void {
        $schema = $this->db->getSchema();
        $quoter = $this->db->getQuoter();
        $tableRawNameSourceMessage = $quoter->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $quoter->getRawTableName($tableMessage);

        if ($schema->hasTable($tableRawNameSourceMessage, refresh: true)
            && $schema->hasTable($tableRawNameMessage)
        ) {
            return;
        }

        $this->createSchema($tableRawNameSourceMessage, $tableRawNameMessage);
    }

    /**
     * Ensures that the translator tables does not exist in the database.
     *
     * @param string $tableSourceMessage The name of the source messages tables, `{{%yii_source_message}}` by default.
     * @param string $tableMessage The name of the locale messages tables, `{{%yii_message}}` by default.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function ensureNoTables(
        string $tableSourceMessage = '{{%yii_source_message}}',
        string $tableMessage = '{{%yii_message}}',
    ): void {
        $schema = $this->db->getSchema();
        $quoter = $this->db->getQuoter();
        $tableRawNameSourceMessage = $quoter->getRawTableName($tableSourceMessage);
        $tableRawNameMessage = $quoter->getRawTableName($tableMessage);

        if ($schema->hasTable($tableRawNameMessage, refresh: true)) {
            // drop table `yii_message`.
            $this->db->createCommand()->dropTable($tableRawNameMessage)->execute();
        }

        if ($schema->hasTable($tableRawNameSourceMessage)) {
            // drop table `yii_source_message`.
            $this->db->createCommand()->dropTable($tableRawNameSourceMessage)->execute();
        }
    }

    private function createIndex(string $tableSourceMessage, string $tableMessage): void
    {
        $command = $this->db->createCommand();

        $command
            ->createIndex($tableSourceMessage, "IDX_{$tableSourceMessage}_category", 'category')
            ->execute();

        $command
            ->createIndex($tableMessage, "IDX_{$tableMessage}_locale", 'locale')
            ->execute();
    }

    /**
     * Create schema for tables in the database.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    private function createSchema(
        string $tableSourceMessage,
        string $tableMessage,
    ): void {
        $driverName = $this->db->getDriverName();
        $columnBuilder = $this->db->getColumnBuilderClass();

        // create table `yii_sorce_message`.
        $this->db
            ->createCommand()
            ->createTable(
                $tableSourceMessage,
                [
                    'id' => $columnBuilder::primaryKey(),
                    'category' => $columnBuilder::string(),
                    'message_id' => $columnBuilder::text(),
                    'comment' => $columnBuilder::text(),
                ],
            )
            ->execute();

        $columns = [
            'id' => $columnBuilder::integer()->notNull(),
            'locale' => $columnBuilder::string(16)->notNull(),
            'translation' => $columnBuilder::text(),
            'PRIMARY KEY ([[id]], [[locale]])',
        ];

        if ($driverName === 'mysql') {
            $columns[] = "CONSTRAINT FK_{$tableSourceMessage}_{$tableMessage}"
                . " FOREIGN KEY ([[id]]) REFERENCES {$tableMessage} ([[id]])"
                . ' ON DELETE CASCADE ON UPDATE RESTRICT';
        } else {
            $foreignKey = new ForeignKey(
                foreignTableName: $tableSourceMessage,
                foreignColumnNames: ['id'],
                onDelete: ReferentialAction::CASCADE,
                onUpdate: ReferentialAction::RESTRICT,
            );
            $columns['id']->reference($foreignKey);
        }

        // create table `yii_message`.
        $this->db
            ->createCommand()
            ->createTable($tableMessage, $columns)
            ->execute();

        $this->createIndex($tableSourceMessage, $tableMessage);
    }
}
