<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Migrations;

use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M201104110256CreateMessageSource
 */
final class M201104110256CreateMessageSource implements RevertibleMigrationInterface
{
    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function up(MigrationBuilder $b): void
    {
        $tableOptions = null;

        if ($b->getDb()->getName() === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $columnsMessage = match ($b->getDb()->getName()) {
            'sqlite' => [
                'id' => 'integer NOT NULL REFERENCES `source_message` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE',
                'locale' => $b->string(16)->notNull(),
                'translation' => $b->text(),
                'PRIMARY KEY (`id`, `locale`)',
            ],
            default => [
                'id' => $b->integer()->notNull(),
                'locale' => $b->string(16)->notNull(),
                'translation' => $b->text(),
            ],
        };

        $b->createTable('{{%source_message}}', [
            'id' => $b->primaryKey(),
            'category' => $b->string(),
            'message_id' => $b->text(),
            'comment' => $b->text(),
        ], $tableOptions);

        $b->createTable('{{%message}}', $columnsMessage, $tableOptions);

        if ($b->getDb()->getName() !== 'sqlite') {
            $b->addPrimaryKey('{{%message}}', 'pk_message_id_locale', ['id', 'locale']);
        }

        $onUpdateConstraint = 'RESTRICT';

        if ($b->getDb()->getName() === 'sqlsrv') {
            // 'NO ACTION' is equivalent to 'RESTRICT' in MSSQL
            $onUpdateConstraint = 'NO ACTION';
        }

        if ($b->getDb()->getName() !== 'sqlite') {
            $b->addForeignKey(
                '{{%message}}',
                'fk_message_source_message',
                'id',
                '{{%source_message}}',
                'id',
                'CASCADE',
                $onUpdateConstraint
            );
        }

        $b->createIndex('{{%source_message}}', 'idx_source_message_category', 'category');
        $b->createIndex('{{%message}}', 'idx_message_locale', 'locale');
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function down(MigrationBuilder $b): void
    {
        if ($b->getDb()->getName() !== 'sqlite') {
            $b->dropForeignKey('{{%message}}', 'fk_message_source_message');
        }

        $b->dropTable('{{%message}}');
        $b->dropTable('{{%source_message}}');
    }
}
