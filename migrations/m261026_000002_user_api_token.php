<?php

use yii\db\Migration;

/**
 * Персональный API-токен пользователя. Храним только SHA-256 хеш:
 * сам токен показывается один раз при генерации.
 */
class m261026_000002_user_api_token extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'api_token_hash', $this->string(64)->null());
        $this->addColumn('{{%user}}', 'api_token_hint', $this->string(12)->null());
        $this->addColumn('{{%user}}', 'api_token_created_at', $this->integer()->null());
        $this->createIndex('idx_user_api_token', '{{%user}}', 'api_token_hash', true);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_user_api_token', '{{%user}}');
        $this->dropColumn('{{%user}}', 'api_token_created_at');
        $this->dropColumn('{{%user}}', 'api_token_hint');
        $this->dropColumn('{{%user}}', 'api_token_hash');
    }
}
