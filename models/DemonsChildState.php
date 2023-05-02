<?php

namespace app\modules\Yii2DemonTasker\models;

use Yii;

/**
 * This is the model class for table "demons_child_state".
 *
 * @property int $pid_main
 * @property int $pid
 * @property int $run
 * @property string $date_run
 * @property int $memory_use
 */
class DemonsChildState extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'demons_child_state';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid_main', 'pid'], 'required'],
            [['pid_main', 'pid', 'is_run', 'memory_use'], 'integer'],
            [['date_run'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'pid_main' => 'Pid Main',
            'pid' => 'Pid',
            'is_run' => 'Is Run',
            'date_run' => 'Date Run',
            'memory_use' => 'Memory Use',
        ];
    }

    /**
     * {@inheritdoc}
     * @return DemonsChildStateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new DemonsChildStateQuery(get_called_class());
    }

        public function getActive()
    {
        return abs($this->is_active) === 1;
    }

    public function setActive($val)
    {
        $this->is_active = ($val !== true ? 0 : 1);
    }

    public function getRun()
    {
        return abs($this->is_run) === 1;
    }

    public function setRun($val)
    {
        $this->is_run = ($val !== true ? 0 : 1);
    }
}
