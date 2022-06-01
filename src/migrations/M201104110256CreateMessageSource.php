<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\migrations;

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M201104110256CreateMessageSource
 */
final class M201104110256CreateMessageSource implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $tableOptions = null;

        if ($b
                ->getDb()
                ->getDriverName() === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $b->createTable('{{%source_message}}', [
            'id' => $b->primaryKey(),
            'category' => $b->string(),
            'message_id' => $b->text(),
            'comment' => $b->text(),
        ], $tableOptions);

        $b->createTable('{{%message}}', [
            'id' => $b
                ->integer()
                ->notNull(),
            'locale' => $b
                ->string(16)
                ->notNull(),
            'translation' => $b->text(),
        ], $tableOptions);

        $b->addPrimaryKey('pk_message_id_locale', '{{%message}}', ['id', 'locale']);
        $onUpdateConstraint = 'RESTRICT';
        if ($b
                ->getDb()
                ->getDriverName() === 'sqlsrv') {
            // 'NO ACTION' is equivalent to 'RESTRICT' in MSSQL
            $onUpdateConstraint = 'NO ACTION';
        }
        $b->addForeignKey('fk_message_source_message', '{{%message}}', 'id', '{{%source_message}}', 'id', 'CASCADE', $onUpdateConstraint);
        $b->createIndex('idx_source_message_category', '{{%source_message}}', 'category');
        $b->createIndex('idx_message_locale', '{{%message}}', 'locale');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('fk_message_source_message', '{{%message}}');
        $b->dropTable('{{%message}}');
        $b->dropTable('{{%source_message}}');
    }
}
