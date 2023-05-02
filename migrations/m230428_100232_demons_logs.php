<?php

namespace app\modules\Yii2DemonTasker\migrations;

use yii\db\Migration;

/**
 * Class m230428_100232_demons_logs
 */
class m230428_100232_demons_logs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*
         CREATE TABLE `demons_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `main_pid` INT(11) NOT NULL,
            `child_pid` INT(11) NOT NULL,
            `date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `label` VARCHAR(150) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
            `task` VARCHAR(150) NOT NULL COLLATE 'utf8_general_ci',
            `type` ENUM('error','warning','info','debug') NOT NULL COLLATE 'utf8_general_ci',
            PRIMARY KEY (`id`) USING BTREE
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB
        ;

         * */

        $this->createTable('demons_logs', [
            'id' => $this->primaryKey(),
            'main_pid' => $this->integer(11)->notNull(),
            'child_pid' => $this->integer(11)->notNull(),
            'date_create' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'label' => $this->string(150)->notNull(),
            'task' => $this->string(150)->notNull(),
            'type' => "ENUM('error','warning','info','debug')"
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230428_100232_demons_logs cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230428_100232_demons_logs cannot be reverted.\n";

        return false;
    }
    */
}
