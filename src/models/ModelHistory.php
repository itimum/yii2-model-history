<?php

namespace itimum\modelHistory\models;


use yii\db\ActiveRecord;

class ModelHistory extends ActiveRecord
{
    public function getFields(){
        return $this->hasMany(ModelHistoryFields::class, [
            'model_history_id' => 'id',
        ]);
    }
}