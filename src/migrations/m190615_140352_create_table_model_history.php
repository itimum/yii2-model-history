<?php

use yii\db\Migration;

/**
 * Class m190614_103022_create_table_model_history
 */
class m190615_140352_create_table_model_history extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%model_history%}}', [
            'id' => $this->primaryKey(),
            'entity' => $this->string()->notNull(),
            'entity_id' => $this->integer()->notNull(),
            'created_at' => $this->timestamp(),
            'created_by' => $this->integer()->notNull(),
        ]);

        /*$this->addForeignKey('fk-user-model_history', '{{%model_history%}}', 'created_by',
            '{{%user%}}', 'id');*/
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%model_history%}}');
    }
}
