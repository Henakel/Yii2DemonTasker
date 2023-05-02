<?php

namespace app\modules\Yii2DemonTasker\migrations;

use yii\db\Migration;

/**
 * Class m230428_100232_demons_logs
 */
class m230428_100242_demons_runtime extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*
         CREATE TABLE `demons_runtime` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `unit` VARCHAR(150) NOT NULL COLLATE 'utf8_general_ci',
            `task` VARCHAR(150) NOT NULL COLLATE 'utf8_general_ci',
            `last_run` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `unit` (`unit`) USING BTREE,
            INDEX `task` (`task`) USING BTREE,
            INDEX `unit_task` (`unit`, `task`) USING BTREE
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB
        ;
         * */

        $this->createTable('demons_runtime', [
            'id' => $this->primaryKey(),
            'unit' => $this->string(150)->notNull(),
            'unit_id' => $this->integer(11)->notNull(),
            'task' => $this->string(150)->notNull(),
            'last_run' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('unit_unit_id', 'demons_runtime', ['unit', 'unit_id']);

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
