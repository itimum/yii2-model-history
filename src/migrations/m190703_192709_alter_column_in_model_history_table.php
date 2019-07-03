<?php

use yii\db\Migration;

/**
 * Class m190703_192709_alter_column_in_model_history_table
 */
class m190703_192709_alter_column_in_model_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%model_history%}}', 'created_by', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%model_history%}}', 'created_by', $this->integer()->notNull());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190703_192709_alter_column_in_model_history_table cannot be reverted.\n";

        return false;
    }
    */
}
