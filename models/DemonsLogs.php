<?php

namespace app\modules\Yii2DemonTasker\models;

use Yii;

/**
 * This is the model class for table "demons_logs".
 *
 * @property int $id
 * @property int $main_pid
 * @property int $child_pid
 * @property string $date_create
 * @property string $label
 * @property string $task
 * @property string|null $type
 */
class DemonsLogs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'demons_logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'main_pid', 'child_pid', 'label', 'task'], 'required'],
            [['id', 'main_pid', 'child_pid'], 'integer'],
            [['date_create'], 'safe'],
            [['type'], 'string'],
            [['label', 'task'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'main_pid' => 'Main Pid',
            'child_pid' => 'Child Pid',
            'date_create' => 'Date Create',
            'label' => 'Label',
            'task' => 'Task',
            'type' => 'Type',
        ];
    }

    /**
     * {@inheritdoc}
     * @return DemonsLogsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new DemonsLogsQuery(get_called_class());
    }
}
