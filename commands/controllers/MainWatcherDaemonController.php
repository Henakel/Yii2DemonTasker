<?php

namespace app\commands\controllers;

use app\modules\Yii2DemonTasker\models\DemonsState;
use app\modules\Yii2DemonTasker\models\DemonsStateQuery;

class MainWatcherDaemonController extends DaemonController
{
    public function init()
    {
        $state = $this->getStateFromName($this->shortClassName());
        exec("ps -p {$state->pid}", $output);
        if (count($output) > 1) {
            $this->halt(self::EXIT_CODE_ERROR, 'Another Watcher is already running.');
        }
        parent::init();
    }

    /**
     * Job processing body
     *
     * @param $job DemonsState
     * @return boolean
     */
    protected function doJob($job)
    {
        \Yii::debug('Check:' . $job->name);

        if (intval($job->pid) < 5) {
            sleep(1);
            $job->refresh();
        }

        if ($this->isProcessRunning($job->pid)) {
            \Yii::debug($job->name . ':isRun');
            if ($job->active) {
                return true;
            }
            else {
                \Yii::warning($job->name . ':setNoActive => posix_kill:' . SIGTERM);
                posix_kill($job->pid, SIGTERM);
                return true;
            }
        }
        else {
            if ($job->active) {
                \Yii::debug($job->name . ':starting');
                $command_name = $this->getCommandNameBy($job->name);

                \Yii::$app->getLog()->getLogger()->flush(true);
                $pid = pcntl_fork();

                if ($pid == -1) {
                    $this->halt(self::EXIT_CODE_ERROR, 'pcntl_fork() returned error');
                }
                elseif (!$pid) {
                    $this->initLogger();
                    \Yii::debug($job->name . ':startingOk');
                }
                else {
                    $this->halt(
                    // фактический запуск \Yii::$app->runAction("$command_name", ['demonize' => 1])
                        (0 === \Yii::$app->runAction("$command_name", ['demonize' => 1])
                            ? self::EXIT_CODE_NORMAL
                            : self::EXIT_CODE_ERROR
                        )
                    );
                }
            }
        }

        \Yii::debug($job->name . ' is checked.');

        return true;
    }

    /**
     * Return array of daemons
     *
     * @return array
     */
    protected function defineJobs()
    {
        sleep(1);
        return DemonsState::find()->all();
    }

    protected function getCommandNameBy($className)
    {
        $command = strtolower(
            preg_replace_callback('/(?<!^)(?<![A-Z])[A-Z]{1}/',
                function ($matches) {
                    return '-' . $matches[0];
                },
                str_replace('Controller', '', $className)
            )
        );

        return $command . DIRECTORY_SEPARATOR . 'index';
    }

    /**
     * @param $pid
     * @return bool
     */
    public function isProcessRunning($pid)
    {
        return !!posix_getpgid($pid);
    }
}
