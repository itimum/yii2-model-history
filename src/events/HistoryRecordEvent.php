<?php

namespace itimum\modelHistory\events;

use yii\base\Event;

class HistoryRecordEvent extends Event
{
    public $historyModel;
}