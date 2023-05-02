<?php

namespace app\modules\Yii2DemonTasker\migrations;

use yii\db\Migration;

/**
 * Class m230428_100223_demons_child_state
 */
class m230428_100223_demons_child_state extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*
         CREATE TABLE `demons_child_state` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `pid_main` INT(11) NOT NULL,
            `pid` INT(11) NOT NULL,
            `is_active` TINYINT(4) NOT NULL DEFAULT '0',
            `date_run` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `memory_use` TINYINT(4) NOT NULL DEFAULT '0'
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB
        ;

         * */

        $this->createTable('demons_child_state', [
            'id' => $this->primaryKey(),
            'pid_main' => $this->integer(11)->notNull(),
            'pid' => $this->integer(11)->notNull(),
            'is_run' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'date_run' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'memory_use' => $this->bigInteger(20)->notNull()->defaultValue(0)
        ]);
        /*
         ALTER TABLE `demons_child_state`
            ADD INDEX `pid_main_pid_is_active` (`pid_main`, `pid`, `is_active`),
            ADD INDEX `pid_main_pid` (`pid_main`, `pid`);
         * */

        $this->createIndex('pid_main_pid', 'demons_child_state', ['pid_main', 'pid']);
        $this->createIndex('pid_main_pid_is_active', 'demons_child_state', ['pid_main', 'pid', 'is_active']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230428_100223_demons_child_state cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230428_100223_demons_child_state cannot be reverted.\n";

        return false;
    }
    */
}
