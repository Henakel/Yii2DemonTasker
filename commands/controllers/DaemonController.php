<?php

namespace app\commands\controllers;

use app\modules\Yii2DemonTasker\models\DemonsRuntime;
use app\modules\Yii2DemonTasker\models\DemonsState;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\console\Controller;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;


abstract class DaemonController extends Controller
{

    const EVENT_BEFORE_JOB = "EVENT_BEFORE_JOB";
    const EVENT_AFTER_JOB  = "EVENT_AFTER_JOB";

    const EVENT_BEFORE_ITERATION = "event_before_iteration";
    const EVENT_AFTER_ITERATION  = "event_after_iteration";

    /**
     * @var $demonize boolean Run controller as Daemon
     * @default false
     */
    public $demonize = false;

    /**
     * @var $isMultiInstance boolean allow daemon create a few instances
     * @see $maxChildProcesses
     * @default false
     */
    public $isMultiInstance = false;

    public $unitName    = '';
    public $unitList    = [];
    public $unitRunTime = [];

    /**
     * @var $maxChildProcesses int max daemon instances
     * @default 10
     */
    public $maxChildProcesses = 10;

    /**
     * @var $currentJobs [] array of running instances
     */
    protected static $currentJobs = [];
    protected        $mainPid;
    protected        $childPid;

    /**
     * @var int Memory limit for daemon, must bee less than php memory_limit
     * @default 32M
     */
    private $memoryLimit = 268435456;

    /**
     * @var int used for soft daemon stop, set 1 to stop
     */
    private static $stopFlag = 0;

    /**
     * @var int Delay between task list checking
     * @default 5sec
     */
    protected $sleep = 5;

    protected $pidDir = "@runtime/daemons/pids";
    protected $logDir = "@runtime/daemons/logs";

    private $shortName = '';


    /**
     * Init function
     */
    public function init()
    {
        parent::init();

        //set PCNTL signal handlers
        pcntl_signal(SIGTERM, ['app\commands\controllers\DaemonController', 'signalHandler']);
        pcntl_signal(SIGHUP, ['app\commands\controllers\DaemonController', 'signalHandler']);
        pcntl_signal(SIGUSR1, ['app\commands\controllers\DaemonController', 'signalHandler']);
        pcntl_signal(SIGCHLD, ['app\commands\controllers\DaemonController', 'signalHandler']);

        $this->shortName = $this->shortClassName();
    }

    /**
     * Adjusting logger. You can override it.
     */
    protected function initLogger()
    {
        $targets = \Yii::$app->getLog()->targets;
        foreach ($targets as $name => $target) {
            $target->enabled = false;
        }
        $config = [
            'exportInterval' => 1,
            'levels' => ['error', 'warning', 'info'], // 'trace',
            'logFile' => \Yii::getAlias($this->logDir) . DIRECTORY_SEPARATOR . $this->shortName . '.log',
            'logVars' => [],
            'except' => [
                'yii\db\*', // Don't include messages from db
            ],
        ];
        $targets['daemon'] = new \yii\log\FileTarget($config);
        \Yii::$app->getLog()->targets = $targets;
        \Yii::$app->getLog()->init();
    }

    /**
     * Daemon worker body
     *
     * @param $job
     * @return boolean
     */
    abstract protected function doJob($job);


    /**
     * Base action, you can\t override or create another actions
     *
     * @return boolean
     */
    final public function actionIndex()
    {
        if ($this->demonize) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->halt(self::EXIT_CODE_ERROR, 'pcntl_fork() rise error');
            }
            elseif ($pid) {
                $this->halt(self::EXIT_CODE_NORMAL);
            }
            else {
                posix_setsid();
                //close std streams (unlink console)
                if (is_resource(STDIN)) {
                    fclose(STDIN);
                    $stdIn = fopen('/dev/null', 'r');
                }
                if (is_resource(STDOUT)) {
                    fclose(STDOUT);
                    $stdOut = fopen('/dev/null', 'ab');
                }
                if (is_resource(STDERR)) {
                    fclose(STDERR);
                    $stdErr = fopen('/dev/null', 'ab');
                }
            }
        }
        //rename process
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            cli_set_process_title($this->getProcessName());
        }
        else {
            if (function_exists('setproctitle')) {
                setproctitle($this->getProcessName());
            }
            else {
                throw new NotSupportedException(
                    "Can't find cli_set_process_title or setproctitle function"
                );
            }
        }
        //run iterator
        return $this->loop();
    }

    protected function getProcessName()
    {
        return $this->shortName;
    }

    /**
     * Prevent non index action running
     *
     * @param \yii\base\Action $action
     * @return bool
     * @throws NotSupportedException
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $this->initLogger();
            if ($action->id != "index") {
                throw new NotSupportedException(
                    "Only index action allowed in daemons. So, don't create and call another"
                );
            }
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Возвращает доступные опции
     *
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        return [
            'demonize',
            'taskLimit',
            'isMultiInstance',
            'maxChildProcesses'
        ];
    }

    /**
     * Extract current unprocessed jobs
     * You can extract jobs from DB (DataProvider will be great), queue managers (ZMQ, RabbiMQ etc), redis and so on
     *
     * @return array with jobs
     */
    abstract protected function defineJobs();


    /**
     * Fetch one task from array of tasks
     * @param Array
     * @return mixed one task
     */
    protected function defineJobExtractor(&$jobs)
    {
        return array_shift($jobs);
    }

    private function changeState(&$state, $fields)
    {
        /* @var $state \yii\db\ActiveRecord */
        foreach ($fields as $key => $val) {
            $state->$key = $val;
        }
        $state->memory_use = memory_get_usage();

        $state->save();

        if (!empty($state->errors))
            throw new \Exception('Ошибка изменения состояния:' . var_export($state->errors, true));
    }

    /**
     * Main iterator
     *
     * * @return boolean 0|1
     */
    final private function loop()
    {
        $this->mainPid = getmypid();

        $state = $this->getStateFromName($this->getProcessName());
        $this->changeState($state, [
            'pid' => $this->mainPid,
            'run' => true,
            'date_run' => new Expression('NOW()'),
        ]);

        \Yii::info('Daemon ' . $this->shortName . ' pid ' . $this->mainPid . ' started.');
        while ($state->active && (memory_get_usage() < $this->memoryLimit)) {

            $this->mainPid = getmypid();
            $this->changeState($state, ['pid' => $this->mainPid]);

            $this->trigger(self::EVENT_BEFORE_ITERATION);
            $this->renewConnections();
            $jobs = $this->defineJobs();

            if ($jobs && count($jobs)) {
                while (($job = $this->defineJobExtractor($jobs)) !== null) {
                    if (count(static::$currentJobs) >= $this->maxChildProcesses) {
                        \Yii::info('Reached maximum number of child processes. Waiting...');
                        while (count(static::$currentJobs) >= $this->maxChildProcesses) {
                            sleep(1);
                            pcntl_signal_dispatch();
                        }
                        \Yii::info(
                            'Free workers found: ' .
                            ($this->maxChildProcesses - count(static::$currentJobs)) .
                            ' worker(s). Delegate tasks.'
                        );
                    }
                    pcntl_signal_dispatch();
                    $this->runDaemon($job);
                }
            }
            else {
                usleep($this->sleep);
            }
            pcntl_signal_dispatch();
            $this->trigger(self::EVENT_AFTER_ITERATION);
            $state->refresh();
        }

        if (memory_get_usage() > $this->memoryLimit) {
            \Yii::info('Daemon ' . $this->shortName . ' pid ' .
                getmypid() . ' used ' . memory_get_usage() . ' bytes on ' . $this->memoryLimit .
                ' bytes allowed by memory limit');
        }

        \Yii::info('Daemon ' . $this->shortClassName() . ' pid ' . getmypid() . ' is stopped.');
        $this->changeState($state, ['pid' => getmypid(), 'run' => false]);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Completes the process (soft)
     */
    public static function stop()
    {
        self::$stopFlag = 1;
    }

    /**
     * PCNTL signals handler
     *
     * @param $signo
     * @param null $pid
     * @param null $status
     */
    final function signalHandler($signo, $pid = null, $status = null)
    {
        switch ($signo) {
            case SIGTERM:
                //shutdown
                static::stop();
                break;
            case SIGHUP:
                //restart, not implemented
                break;
            case SIGUSR1:
                //user signal, not implemented
                break;
            case SIGCHLD:
                if (!$pid) {
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }
                if (isset($pid['pid']))
                    $pid = $pid['pid'];
                while ($pid > 0) {
                    if ($pid && isset(static::$currentJobs[$pid])) {
                        unset(static::$currentJobs[$pid]);
                        file_put_contents($this->getPidPath() . '_jobs', var_export(static::$currentJobs, true));
                    }
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                    if (isset($pid['pid']))
                        $pid = $pid['pid'];
                }
                break;
        }
    }


    /**
     * Tasks runner
     *
     * @param string $job
     * @return boolean
     */
    final public function runDaemon($job)
    {

        if ($this->isMultiInstance) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                return false;
            }
            elseif ($pid) {
                static::$currentJobs[$pid] = microtime(true);
                $this->childPid = $pid;
            }
            else {
                $this->renewConnections();
                //child process must die
                $this->trigger(self::EVENT_BEFORE_JOB);
                if ($this->doJob($job)) {
                    $this->trigger(self::EVENT_AFTER_JOB);
                    $this->halt(self::EXIT_CODE_NORMAL);
                }
                else {
                    $this->trigger(self::EVENT_AFTER_JOB);
                    $this->halt(self::EXIT_CODE_ERROR, 'Child process #' . $pid . ' return error.');
                }
            }

            return true;
        }
        else {
            $this->childPid = $this->mainPid;

            $this->trigger(self::EVENT_BEFORE_JOB);
            $status = $this->doJob($job);
            $this->trigger(self::EVENT_AFTER_JOB);

            return $status;
        }
    }

    /**
     * Stop process and show or write message
     *
     * @param $code int код завершения -1|0|1
     * @param $message string сообщение
     */
    protected function halt($code, $message = null)
    {
        if ($message !== null) {
            if ($code == self::EXIT_CODE_ERROR) {
                \Yii::info($message);
                if (!$this->demonize) {
                    $message = Console::ansiFormat($message, [Console::FG_RED]);
                }
            }
            else {
                \Yii::info($message);
            }
            if (!$this->demonize) {
                $this->writeConsole($message);
            }
        }
        if ($code !== -1) {
            exit($code);
        }
    }

    /**
     * Renew connections
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    protected function renewConnections()
    {
        if (isset(\Yii::$app->db)) {
            \Yii::$app->db->close();
            \Yii::$app->db->open();
        }
    }

    /**
     * Show message in console
     *
     * @param $message
     */
    private function writeConsole($message)
    {
        $out = Console::ansiFormat('[' . date('d.m.Y H:i:s') . '] ', [Console::BOLD]);
        $this->stdout($out . $message . "\n");
    }

    /**
     * Get classname without namespace
     *
     * @return string
     */
    public function shortClassName()
    {
        $classname = $this->className();

        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
            $classname = $matches[1];
        }

        return $classname;
    }

    public function getPidPath()
    {
        $dir = \Yii::getAlias($this->pidDir);
        if (!file_exists($dir)) {
            mkdir($dir, 0744, true);
        }
        return $dir . DIRECTORY_SEPARATOR . $this->shortName;
    }


    public function getStateFromName($name)
    {
        $pid = DemonsState::find()->pidFromName($name)->one();
        if (empty($pid))
            throw new \Exception('Ошибка поиска состояния основного процесса:' . $name);
        else
            return $pid;
    }


    public function loadUnits(ActiveQuery $query)
    {
        $this->unitList = $query->all();
        $this->unitRunTime = DemonsRuntime::find()->where([
            'unit' => $this->unitName,
            'unit_id' => ArrayHelper::getColumn($this->listUnits, 'id')
        ])->all();
    }


}
