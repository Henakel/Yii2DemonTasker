<?php

namespace app\modules\Yii2DemonTasker\models;

/**
 * This is the ActiveQuery class for [[DemonsLogs]].
 *
 * @see DemonsLogs
 */
class DemonsLogsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return DemonsLogs[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return DemonsLogs|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
