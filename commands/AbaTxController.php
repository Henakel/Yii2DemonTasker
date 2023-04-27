<?php

namespace app\commands;

use app\commands\controllers\DaemonController;
use app\models\Coins;
use app\models\isAbandoned;
use app\models\PayOuts;
use app\models\PayTasks;
use app\models\Telegram;
use app\models\TransactionHistory;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class AbaTxController extends DaemonController
{
    public $sleep             = 1000000;
    public $maxChildProcesses = 1;
    public $isMultiInstance   = false;
    public $lastActive        = 0;
    public $lastActives       = [];
    public $minPeriod         = 20;
    public $minPeriodTx       = 60 * 60;

    public function init()
    {
        parent::init();
        date_default_timezone_set('Europe/Moscow');
    }

    /**
     * @return array
     */
    protected function defineJobs()
    {
        $this->initLogger();

        Yii::$app->settings->clearCache();

        if (Yii::$app->settings->get('active_system', 'Main') != 'Y')
            return [];


        $list = TransactionHistory::find()->andWhere([
            'confirmations' => '0',
        ])->andWhere(['!=', 'status', TransactionHistory::STATUS_ABANDONED])
            ->andWhere(
                ['>=', new Expression('ROUND(time_to_sec((TIMEDIFF(NOW(), date))) / 60, 2)'),
                    (12 * 60)]
            )->all();

        foreach ($list as $historyRow) {
            $leftTime = time() - $this->lastActive;
            $txLeftTime = (!empty($this->lastActives[$historyRow->txid]) ? time() - $this->lastActives[$historyRow->txid] : time());

            if ($txLeftTime < $this->minPeriodTx) {
                continue;
            }

            if ($leftTime < $this->minPeriod) {
                #var_dump('sleep active: ' . ($this->minPeriod - $leftTime), $this->minPeriod, $leftTime);
                sleep(($this->minPeriod - $leftTime));
            }

            /* @var $historyRow TransactionHistory */
            $isAbandoned = isAbandoned::is($historyRow);
            $this->lastActive = time();
            $this->lastActives[$historyRow->txid] = time();

            Yii::error($historyRow->txid . ' isAbandoned::' . ($isAbandoned ? 'true' : 'false'));

            if ($isAbandoned) {
                $historyRow->status = TransactionHistory::STATUS_ABANDONED;

                #var_dump('abaStatus: ' . $historyRow->status);
                if (!$historyRow->save()) {
                    Telegram::error("Ошибка сохранения historyRow:\n" . var_export([$historyRow->txid, $historyRow->errors], true));
                }
            }
        }

        return [];
    }

    protected function doJob($task)
    {
        return true;
    }

    protected function initLogger()
    {
        $targets = \Yii::$app->getLog()->targets;
        foreach ($targets as $name => $target) {
            $target->enabled = false;
        }
        $config = [
            'exportInterval' => 1,
            'levels' => ['error'], //  'warning','info',
            'logFile' => \Yii::getAlias($this->logDir . '/' . date('Y.m.d') . '/') . $this->shortClassName() . '.log',
            'logVars' => [],
            'except' => [
                'yii\db\*', // Don't include messages from db
            ],
        ];
        $targets['daemon'] = new \yii\log\FileTarget($config);
        \Yii::$app->getLog()->flushInterval = 1;
        \Yii::$app->getLog()->targets = $targets;
        \Yii::$app->getLog()->init();
    }


}