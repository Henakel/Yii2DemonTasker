<?php

namespace app\commands\controllers;

use Yii;

class WatcherDaemonController extends MainWatcherDaemonController
{
    /**
     * @return array
     */
    protected function defineJobs()
    {
        sleep($this->sleep);
        $daemons = [
            ['className' => 'MainController', 'enabled' => true],
            ['className' => 'AbaTxController', 'enabled' => true],
        ];
        return $daemons;
    }
}