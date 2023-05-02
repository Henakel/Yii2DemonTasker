<?php

namespace app\modules\Yii2DemonTasker\models;

/**
 * This is the ActiveQuery class for [[DemonsChildState]].
 *
 * @see DemonsChildState
 */
class DemonsChildStateQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return DemonsChildState[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return DemonsChildState|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
