<?php

namespace app\modules\DemonTasker;

/**
 * DemonTasker module definition class
 */
class Module extends \yii\base\Module
{
    public static $addMenu = [
        [
            'title' => 'DemonTasker',
            'href' => '/DemonTasker/apps/index',
            'pattern' => '/settings_',
            'icon' => '<i class="fa-solid fa-rocket"></i>'
        ],
    ];
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\DemonTasker\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'app\modules\DemonTasker\commands\controllers';
        }
    }
}
