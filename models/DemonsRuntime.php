<?php

namespace app\modules\Yii2DemonTasker\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "demons_runtime".
 *
 * @property int $id
 * @property string $unit
 * @property string $task
 * @property string $last_run
 */
class DemonsRuntime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'demons_runtime';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['unit', 'task'], 'required'],
            [['last_run'], 'safe'],
            [['unit', 'task'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'unit' => 'Unit',
            'task' => 'Task',
            'last_run' => 'Last Run',
        ];
    }

}
