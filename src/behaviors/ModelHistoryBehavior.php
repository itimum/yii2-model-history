<?php

namespace itimum\modelHistory\behaviors;

use itimum\modelHistory\events\HistoryRecordEvent;
use itimum\modelHistory\models\ModelHistory;
use itimum\modelHistory\models\ModelHistoryFields;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Class ModelHistoryBehavior
 * @package itimum\modelHistory\behaviors
 *
 * @property ActiveRecord $owner
 */
class ModelHistoryBehavior extends Behavior {

    const EVENT_BEFORE_HISTORY_RECORD = 'beforeHistoryRecord';
    const EVENT_AFTER_HISTORY_RECORD = 'afterHistoryRecord';

    protected $_historyClass = ModelHistory::class;
    protected $_historyFieldsClass = ModelHistoryFields::class;

    protected $_historyOldAttributes = [];

    /**
     * TODO
     * @var array
     */
    public $prepareHistoryData;

    /**
     * TODO
     * @var callable
     */
    public $beforeHistoryModelSave;

    /**
     * TODO
     * @var callable
     */
    public $afterHistoryModelSave;

    /**
     * TODO
     * @var callable
     */
    public $addBeforeListeners;

    /**
     * TODO
     * @var callable
     */
    public $addAfterListeners;

    /**
     * TODO
     * @var array|string
     */
    public $exceptColumns = [];

    /**
     * @param \yii\base\Component $owner
     */
    public function attach($owner) {

        parent::attach($owner);

        if ($this->owner instanceof ActiveRecord) {
            $this->_addBeforeListeners();
            $this->_addAfterListeners();
        }

    }

    /**
     * Add before action listeners
     */
    protected function _addBeforeListeners() {
        $this->owner->on(ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'setHistoryOldAttributes']);

        $this->owner->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'setHistoryOldAttributes']);

        $this->owner->on(ActiveRecord::EVENT_BEFORE_DELETE, [$this, 'setHistoryOldAttributes']);

        if (is_callable($this->addBeforeListeners)) {
            call_user_func($this->addBeforeListeners);
        }
    }

    /**
     * Add after action listeners
     */
    protected function _addAfterListeners() {
        $this->owner->on(ActiveRecord::EVENT_AFTER_UPDATE, [$this, 'addHistoryRecord']);

        $this->owner->on(ActiveRecord::EVENT_AFTER_INSERT, [$this, 'addHistoryRecord']);

        $this->owner->on(ActiveRecord::EVENT_AFTER_DELETE, [$this, 'addHistoryRecord']);

        if (is_callable($this->addAfterListeners)) {
            call_user_func($this->addAfterListeners);
        }
    }

    /**
     * Create history field models and return array of them
     *
     * @param $event
     *
     * @return array|mixed
     */
    protected function _prepareHistoryData($event) {

        $data = [];

        if($dirtyAttributes = $this->getDirtyAttributes()) {
            // если мы создаем новую запись то не пишем старые данные так как они одинаковые
            if (!$this->owner->getIsNewRecord()) {
                if(!empty($this->exceptColumns)) {
                    if(is_array($this->exceptColumns)) {
                        foreach ($this->exceptColumns as $column) {
                            $this->deleteExceptColumn($dirtyAttributes, $column);
                        }
                    } else if (is_string($this->exceptColumns)) {
                        $this->deleteExceptColumn($dirtyAttributes, $this->exceptColumns);
                    }
                }
                // получаем старые значения измененных атрибутов
                $oldAttributes = array_intersect_key($this->_historyOldAttributes, $dirtyAttributes);

                foreach ($dirtyAttributes as $key=>$value) {
                    $model = new ModelHistoryFields();
                    $model->field_name = $key;
                    $model->new_value = $value;
                    if (array_key_exists($key, $oldAttributes)) {
                        $model->old_value = $oldAttributes[$key];
                    }
                    $data[$key] = $model;
                }
            }
        }

        if (is_callable($this->prepareHistoryData)) {
            $data = call_user_func($this->prepareHistoryData, $event, $data);
        }
        return $data;
    }

    /**
     * Метод удаляет ненужные для записи в историю поля
     *
     * @param $dirtyAttributes
     * @param $column
     */
    protected function deleteExceptColumn(&$dirtyAttributes, $column) {
        if(isset($this->_historyOldAttributes[$column])) {
            unset($this->_historyOldAttributes[$column]);
        }

        if(isset($dirtyAttributes[$column])) {
            unset($dirtyAttributes[$column]);
        }
    }

    /**
     * Записывает в базу историю изменений модели
     *
     * @param $event
     *
     * @throws \yii\db\Exception
     */
    public function addHistoryRecord($event) {

        $data = $this->_prepareHistoryData($event);

        // если у нас не изменились данные в модели ничего не пишем в историю
        if(!empty($data)) {

            $historyModel = $this->_createHistoryModel();

            /**
             * TODO
             */
            if (is_callable($this->beforeHistoryModelSave)) {
                call_user_func($this->beforeHistoryModelSave, $event, $historyModel);
            }
            $this->owner->trigger(self::EVENT_BEFORE_HISTORY_RECORD,
                    new HistoryRecordEvent(['historyModel' => $historyModel]));

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $historyModel->save();

                foreach ($data as $model) {
                    $model->model_history_id = $historyModel->id;
                    $model->save();
                }

                $transaction->commit();
            } catch (Exception $exception) {
                $transaction->rollBack();

                throw $exception;
            }

            /**
             * TODO
             */
            if (is_callable($this->afterHistoryModelSave)) {
                call_user_func($this->afterHistoryModelSave, $event, $historyModel);
            }
            $this->owner->trigger(self::EVENT_AFTER_HISTORY_RECORD,
                    new HistoryRecordEvent(['historyModel' => $historyModel]));

        }
    }

    /**
     * Метод возвращает полное название класса модели
     *
     * @return string
     */
    public function getEntityName() {
        return get_class($this->owner);
    }

    /**
     * Создает модель истории
     *
     * @return ActiveRecord
     */
    protected function _createHistoryModel() {

        $historyRecord = new $this->_historyClass;

        $historyRecord->created_by = \yii::$app->user->getId();
        $historyRecord->entity_id = $this->owner->id;
        $historyRecord->entity = $this->getEntityName();

        return $historyRecord;
    }

    /**
     * Метод получения изменненных данных взят из ActiveRecord за исключением тождественного равенства
     *
     * @param null $names
     *
     * @return array
     */
    private function getDirtyAttributes($names = null) {
        if ($names === null) {
            $names = $this->owner->attributes();
        }

        $names = array_flip($names);
        $attributes = [];
        if ($this->_historyOldAttributes === null) {
            foreach ($this->owner->getOldAttributes() as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($this->owner->getOldAttributes() as $name => $value) {
                // не точное сравнение, убрал !== на !=, иначе надо каждый массив прогонять и проставлять тип данных int
                if (isset($names[$name]) && (!array_key_exists($name, $this->_historyOldAttributes)
                        || $value != $this->_historyOldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * @return array
     */
    public function getHistoryOldAttributes() {
        return $this->_historyOldAttributes;
    }

    /**
     * @param Event $event
     */
    public function setHistoryOldAttributes($event) {
        $this->_historyOldAttributes = $event->sender->getOldAttributes();
    }

    /**
     * Добавляет связь с таблицей истории
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHistory(){
        return $this->owner->hasMany($this->_historyClass, [
            'entity_id' => 'id',
        ])->where(['entity' => get_class($this->owner)]);
    }
}
