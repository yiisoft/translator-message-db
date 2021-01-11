<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\migrations;

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M201104110256CreateMessageSource
 */
final class M201104110256CreateMessageSource extends Migration implements RevertibleMigrationInterface
{
    public function up(): void
    {
        $this->createTable('{{%source_message}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(),
            'message_id' => $this->text(),
            'comment' => $this->text(),
        ]);

        $this->createTable('{{%message}}', [
            'id' => $this->integer()->notNull(),
            'locale' => $this->string(16)->notNull(),
            'translation' => $this->text(),
        ]);

        $this->addPrimaryKey('pk_message_id_locale', '{{%message}}', ['id', 'locale']);
        $onUpdateConstraint = 'RESTRICT';
        if ($this->db->getDriverName() === 'sqlsrv') {
            // 'NO ACTION' is equivalent to 'RESTRICT' in MSSQL
            $onUpdateConstraint = 'NO ACTION';
        }
        $this->addForeignKey('fk_message_source_message', '{{%message}}', 'id', '{{%source_message}}', 'id', 'CASCADE', $onUpdateConstraint);
        $this->createIndex('idx_source_message_category', '{{%source_message}}', 'category');
        $this->createIndex('idx_message_locale', '{{%message}}', 'locale');
    }

    public function down(): void
    {
        $this->dropForeignKey('fk_message_source_message', '{{%message}}');
        $this->dropTable('{{%message}}');
        $this->dropTable('{{%source_message}}');
    }
}
