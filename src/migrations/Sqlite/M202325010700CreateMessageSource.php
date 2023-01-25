<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Migrations\Sqlite;

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M202325010700CreateMessageSource
 */
final class M202325010700CreateMessageSource implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable(
            '{{%source_message}}',
            [
                'id' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                'category' => $b->string(),
                'message_id' => $b->text(),
                'comment' => $b->text(),
            ],
        );

        $b->createTable(
            '{{%message}}',
            [
                'id' => 'integer NOT NULL REFERENCES `source_message` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE',
                'locale' => $b->string(16)->notNull(),
                'translation' => $b->text(),
                'PRIMARY KEY (`id`, `locale`)',
            ],
        );

        $b->createIndex('idx_source_message_category', '{{%source_message}}', 'category');
        $b->createIndex('idx_message_locale', '{{%message}}', 'locale');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('{{%message}}');
        $b->dropTable('{{%source_message}}');
    }
}
