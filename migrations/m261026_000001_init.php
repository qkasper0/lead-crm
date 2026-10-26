<?php

use yii\db\Migration;

/**
 * Базовая схема CRM: пользователи, лиды, комментарии, история изменений.
 */
class m261026_000001_init extends Migration
{
    public function safeUp()
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'email' => $this->string(150)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'role' => $this->string(20)->notNull()->defaultValue('manager'),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
        ], $options);

        $this->createTable('{{%lead}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(150)->notNull(),
            'phone' => $this->string(32)->notNull(),
            'source' => $this->string(100)->notNull()->defaultValue('website'),
            'status' => $this->string(20)->notNull()->defaultValue('new'),
            'manager_id' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('idx_lead_status', '{{%lead}}', 'status');
        $this->createIndex('idx_lead_manager_status', '{{%lead}}', ['manager_id', 'status']);
        $this->createIndex('idx_lead_phone', '{{%lead}}', 'phone');
        $this->createIndex('idx_lead_created', '{{%lead}}', 'created_at');
        $this->fk('fk_lead_manager', '{{%lead}}', 'manager_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('{{%lead_comment}}', [
            'id' => $this->primaryKey(),
            'lead_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->null(),
            'text' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('idx_comment_lead', '{{%lead_comment}}', 'lead_id');
        $this->fk('fk_comment_lead', '{{%lead_comment}}', 'lead_id', '{{%lead}}', 'id', 'CASCADE', 'CASCADE');
        $this->fk('fk_comment_user', '{{%lead_comment}}', 'user_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('{{%lead_history}}', [
            'id' => $this->primaryKey(),
            'lead_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->null(),
            'field' => $this->string(30)->notNull(),
            'old_value' => $this->string(255)->null(),
            'new_value' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('idx_history_lead', '{{%lead_history}}', ['lead_id', 'created_at']);
        $this->fk('fk_history_lead', '{{%lead_history}}', 'lead_id', '{{%lead}}', 'id', 'CASCADE', 'CASCADE');
        $this->fk('fk_history_user', '{{%lead_history}}', 'user_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
    }

    /** Внешние ключи только для MySQL: SQLite (тесты) не умеет добавлять их через ALTER. */
    private function fk(string $name, string $table, string $column, string $refTable, string $refColumn, string $delete, string $update): void
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey($name, $table, $column, $refTable, $refColumn, $delete, $update);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%lead_history}}');
        $this->dropTable('{{%lead_comment}}');
        $this->dropTable('{{%lead}}');
        $this->dropTable('{{%user}}');
    }
}
