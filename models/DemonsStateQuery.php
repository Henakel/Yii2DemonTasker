<?php

namespace app\modules\Yii2DemonTasker\models;

/**
 * This is the ActiveQuery class for [[DemonsState]].
 *
 * @see DemonsState
 */
class DemonsStateQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['is_active' => 1]);
    }

    public function pidFromName($name)
    {
        return $this->andWhere(['name' => $name]);
    }

    /**
     * {@inheritdoc}
     * @return DemonsState[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return DemonsState|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
