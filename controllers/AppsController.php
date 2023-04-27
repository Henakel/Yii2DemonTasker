<?php

namespace app\modules\DemonTasker\controllers;

use yii\web\Controller;

/**
 * Default controller for the `DemonTasker` module
 */
class AppsController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
