<?php

namespace app\modules\Yii2DemonTasker\migrations;

use yii\db\Migration;

/**
 * Class m230428_100211_demons_state
 */
class m230428_100211_demons_state extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*
         CREATE TABLE `demons_state` (
        	`id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(250) NOT NULL COLLATE 'utf8_general_ci',
            `pid` INT(11) NULL DEFAULT NULL,
            `is_run` TINYINT(4) NOT NULL DEFAULT '0',
            `is_active` TINYINT(4) NOT NULL DEFAULT '0',
            `date_run` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `memory_use` BIGINT(20) NOT NULL DEFAULT '0'
        )
        COLLATE='utf8_general_ci'
        ENGINE=InnoDB
        ;
         * */
        $this->createTable('demons_state', [
            'id' => $this->primaryKey(),
            'name' => $this->string(250)->notNull(),
            'pid' => $this->integer(11)->defaultValue(null),
            'is_run' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'date_run' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'memory_use' => $this->bigInteger(20)->notNull()->defaultValue(0),
        ]);

        #ALTER TABLE `demons_state` ADD UNIQUE INDEX `name` (`name`);
        $this->createIndex('name', 'demons_state', ['name'], true);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230428_100211_demons_state cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230428_100211_demons_state cannot be reverted.\n";

        return false;
    }
    */
}
