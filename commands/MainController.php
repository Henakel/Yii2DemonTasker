<?php

namespace app\commands;

use app\commands\controllers\DaemonController;
use app\models\Coins;
use app\models\PayOuts;
use app\models\PayTasks;
use app\models\Telegram;
use app\models\TransactionHistory;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class MainController extends DaemonController
{
    public  $sleep             = 1000000;
    public  $maxChildProcesses = 1;
    public  $isMultiInstance   = false;
    private $issetNow          = [];

    public static $taskList = [
        'dumpWallet' => '3600',
        'updateTransactions' => '5',
        'payOuts' => '1',
        //каждые 5 мин проверяем не подтвержденные транзакции отдельно. биток уходит за листинг в 1000
        'updateOldTransactions' => '300',
    ];


    private function dumpWallet($coin)
    {
        /* @var $coin Coins */
        $coin->client()->dumpwallet();
    }

    private function updateTransactions($coin)
    {
        /* @var $coin Coins */
        $this->issetNow = $coin->updateTransactions($this->issetNow);
    }

    private function payOuts($coin)
    {
        /* @var $coin Coins */
        $maxTimeWait = Yii::$app->settings->get('wait_from_payouts', 'Main')[$coin->coin];

        if (PayTasks::find()
                ->andWhere(['status' => PayTasks::STATUS_WAIT])
                ->andWhere(['coin' => $coin->coin])
                ->andWhere(['init' => 'N'])
                ->andWhere(['>=', new Expression('ROUND(time_to_sec((TIMEDIFF(NOW(), date_create))) / 60, 2)'), $maxTimeWait])
                ->count() > 0
        ) {
            Telegram::log("<b>{$coin->coin}</b>\n" . 'Инициализация выплаты.');

            $tasks = PayTasks::find()
                ->andWhere(['status' => PayTasks::STATUS_WAIT])
                ->andWhere(['coin' => $coin->coin])
                ->andWhere(['init' => 'N'])
                ->all();

            Telegram::log("<b>{$coin->coin}</b>\n" . 'Всего задач выплаты: ' . count($tasks));
            Telegram::log("<b>{$coin->coin}</b>\n" . 'Блокируем задачи');

            //Блокировка задач
            PayTasks::updateAll(['init' => 'Y'], ['in', 'id', ArrayHelper::getColumn($tasks, 'id')]);

            $idList = [];
            $saveTaskList = [];
            $addressSum = [];
            foreach ($tasks as $task) {
                /* @var $task PayTasks */
                $idList[] = $task->id;
                Telegram::log('foreach ($tasks as $task) => ' . "{$task->id}\n" . $task->amount);

                if (!isset($saveTaskList[$task->address])) {
                    $saveTaskList[$task->address] = [
                        'id' => strval($task->id),
                        'merchant_name' => $task->merchant_name,
                        'merchant_id' => [strval($task->merchant_id)],
                        'merchant_sum' => $task->merchant_sum,
                        'amount' => $task->amount,
                        'address' => $task->address,
                    ];
                }
                else {
                    $saveTaskList[$task->address]['merchant_id'] = explode(', ', $saveTaskList[$task->address]['merchant_id']);
                    $saveTaskList[$task->address]['merchant_id'][] = $task->merchant_id;

                    $saveTaskList[$task->address]['amount'] += $task->amount;
                    $saveTaskList[$task->address]['merchant_sum'] += $task->merchant_sum;
                }

                $saveTaskList[$task->address]['merchant_id'] = implode(', ', $saveTaskList[$task->address]['merchant_id']);

                $saveTaskList[$task->address]['amount'] = number_format($saveTaskList[$task->address]['amount'], 8, '.', '');
                $addressSum[$task->address] = $saveTaskList[$task->address]['amount'];
                Telegram::log("<b>{$coin->coin}</b>\n" . var_export($saveTaskList, true));
                Telegram::log("<b>{$coin->coin}</b>\n" . var_export($addressSum, true));
            }
            $saveTaskList = array_values($saveTaskList);

            $payOut = new PayOuts([
                'tasks_list' => $saveTaskList,
                'coin' => $coin->coin,
                'status' => 'tmp'
            ]);

            if ($payOut->save()) {
                Telegram::log("<b>{$coin->coin}</b>\n" . "Сформирована временная задача выплаты");

                $statusPay = false;
                $txid = 'none';
                $answer = null;

                try {
                    $txid = $coin->client()->sendmany($addressSum);
                    $answer = $coin->client()->lastError();
                    $statusPay = !empty($txid);
                } catch (\Exception $e) {
                    $answer = $e->getMessage();
                }

                $payOut->answer = $answer;
                $payOut->txid = $txid;

                if ($statusPay) {
                    Telegram::log("<b>{$coin->coin}</b>\n" . "Успешная выплата:\n" . $txid);
                    $payOut->status = PayOuts::STATUS_OK;
                    $payOut->txdata = $coin->client()->transaction($txid);
                }
                else {
                    Telegram::error("<b>{$coin->coin}</b>\n" . "Ошибка выплаты:\n" . $answer);
                    $payOut->status = PayOuts::STATUS_ERROR;
                }

                if (!$payOut->save()) {
                    Telegram::error("<b>{$coin->coin}</b>\n" . "Ошибка выплата:\n" . $answer);
                }
                PayTasks::updateAll(['status' => $payOut->status], ['in', 'id', $idList]);
            }
            else {
                Telegram::error("<b>{$coin->coin}</b>\n" . 'Ошибка формирования задачи выплаты:' . "\n" . var_export($payOut->errors, true));
            }

            Telegram::log("<b>{$coin->coin}</b>\n" . 'Инициализация выплаты. END');
        }
    }

    private function updateOldTransactions($coin)
    {
        /* @var $coin Coins */
        $list = TransactionHistory::find()->andWhere([
            'coin' => $coin->coin,
            'confirmations' => '0'
        ])
            ->andWhere(['!=', 'status', TransactionHistory::STATUS_ABANDONED])
            ->all();

        foreach ($list as $historyRow) {
            /* @var $historyRow TransactionHistory */

            $transaction = $coin->client()->transaction($historyRow->txid);
            $historyRow->confirmations = $transaction['confirmations'];
            $historyRow->status = $historyRow->calcStatus();
            $historyRow->save();

            if (!empty($historyRow->errors))
                Telegram::error("Ошибка сохранения historyRow:\n" . var_export([$historyRow->txid, $historyRow->errors], true));
        }
    }

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

        foreach (array_keys(Coins::CLIENTS) as $coin) {
            /* @var $coin Coins */
            $coin = Coins::initClient($coin);

            if ($coin->client()->isLoadClient() === true) {
                foreach (self::$taskList as $task => $period) {
                    $this->runTask($coin, $task, $period);
                }
            }
        }

        return [];
    }

    private function runTask($coin, $key, $secPeriod)
    {
        /* @var $coin Coins */
        $keyCoin = 'MT_' . $key . '_' . $coin->coin;
        $keyCoin = str_replace('_', '', $keyCoin);
        $out = \Yii::$app->cache->get($keyCoin);
        if (!$out) {
            $this->$key($coin);
            \Yii::$app->cache->set($keyCoin, time(), $secPeriod);
        }
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