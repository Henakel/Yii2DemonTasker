<?php

namespace app\modules\Yii2DemonTasker\models;

use Yii;

/**
 * This is the model class for table "demons_state".
 *
 * @property string $name
 * @property int|null $pid
 * @property int $run
 * @property boolean $active
 * @property string $date_run
 * @property int $memory_use
 */
class DemonsState extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'demons_state';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['pid', 'is_run', 'is_active', 'memory_use'], 'integer'],
            [['date_run'], 'safe'],
            [['name'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Name',
            'pid' => 'Pid',
            'is_run' => 'Is Run',
            'is_active' => 'Is Active',
            'date_run' => 'Date Run',
            'memory_use' => 'Memory Use',
        ];
    }

    /**
     * {@inheritdoc}
     * @return DemonsStateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new DemonsStateQuery(get_called_class());
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
