<?php

namespace app\commands;

use app\commands\controllers\DaemonController;
use app\models\Coins;
use app\models\PayOuts;
use app\models\PayTasks;
use app\models\TaskerTester;
use app\models\Telegram;
use app\models\TransactionHistory;
use app\modules\Yii2DemonTasker\models\DemonsRuntime;
use pheme\settings\models\Setting;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/*
Пример запуска многопоточной обработки юнитов с контролем частоты через таблицу demons_runtime

Юниты обработки должны иметь реляцию на модель  DemonsRuntime для привязки меток запуска
public function getDemonRuntime()
{
    return $this->hasMany(DemonsRuntime::className(), [
        'unit' => new Expression(self::tableName()),
        'unit_id' => 'id',
    ]);
}

 * */

class VariationTasker extends DaemonController
{
    public $sleep             = 1000000;
    public $maxChildProcesses = 10;
    public $isMultiInstance   = true;
    public $unitName          = 'tasker_tester';

    /**
     * @return array
     */
    protected function defineJobs()
    {
        $this->initLogger();
        $this->loadUnits(TaskerTester::find()->limit(100));
        return $this->unitList;
    }

    protected function doJob($task)
    {
        /* @var $task TaskerTester */

        $task->r_task_1 = $task->task1();
        $task->r_task_2 = $task->task2();
        $task->save();

        return true;
    }

}