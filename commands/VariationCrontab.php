<?php

namespace app\commands;

use app\commands\controllers\DaemonController;
use app\models\Coins;
use app\models\PayOuts;
use app\models\PayTasks;
use app\models\TaskerTester;
use app\models\Telegram;
use app\models\TransactionHistory;
use pheme\settings\models\Setting;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/*
 Простой пример запуска задачника в 1 поток без обработчика юнита.
 Контроль частоты делается через задержку между опросами defineJobs
 Больше подходит для задач предназначенных для crontab но требующие более высокой частоты запуска чем раз в мин
  и требующие контроля дублей запуска.

    1) Контроль частоты обработки контролируется через $sleep (usleep 1/1000000 sec)
    2) defineJobs должен возвращять пустой масив
 * */

class VariationCrontab extends DaemonController
{
    public $sleep             = 1000000;
    public $maxChildProcesses = 1;
    public $isMultiInstance   = false;

    /**
     * @return array
     */
    protected function defineJobs()
    {
        $this->initLogger();

        $list = TaskerTester::find()->limit(10)->all();
        foreach ($list as $task) {
            /* @var $task TaskerTester */
            $task->task1();
            $task->task2();
        }

        return [];
    }

    protected function doJob($task)
    {
        return true;
    }

}