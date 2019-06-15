<?php

use yii\db\Migration;

/**
 * Class m190615_140455_create_table_model_history_fields
 */
class m190615_140455_create_table_model_history_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%model_history_fields%}}', [
            'id' => $this->primaryKey(),
            'model_history_id' => $this->integer()->notNull(),
            'field_name' => $this->string()->notNull(),
            'old_value' => $this->string(),
            'new_value' => $this->string(),
        ]);

        $this->addForeignKey('fk-model_history-model_history_fields', '{{%model_history_fields%}}', 'model_history_id',
            '{{%model_history%}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%model_history_fields%}}');
    }
}
