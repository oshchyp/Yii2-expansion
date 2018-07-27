<?php

namespace oshchyp\yii2Expansion\models;

use oshchyp\yii2Expansion\helpers\VarDumper;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Json;

class JsonDataModel extends Model
{
    /**
     * @var string
     */
    protected static $dir = 'test/test';

    /**
     * @var
     */
    protected $id;

    /**
     * @var null
     */
    protected $_oldAttributes=null;

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord()) {
            $this->trigger(ActiveRecord::EVENT_BEFORE_INSERT);
        } else {
            $this->trigger(ActiveRecord::EVENT_BEFORE_UPDATE);
        }

        return true;
    }

    /**
     *
     */
    protected function afterSave()
    {
        if ($this->getIsNewRecord()) {
            $this->trigger(ActiveRecord::EVENT_AFTER_INSERT);
        } else {
            $this->trigger(ActiveRecord::EVENT_AFTER_UPDATE);
        }
    }

    /**
     * @return bool
     */
    public function getIsNewRecord()
    {
        return $this->_oldAttributes === null;
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->validate() && $this->beforeSave()) {
            $this->_save();
            $this->afterSave();
            return true;
        }
        return false;
    }

    /**
     *
     */
    private function _save()
    {
        if (!$this->id) {
            $this->setId();
        }
        $dataSave = $this->toArray();
        if ($this->savingFields()){
            foreach ($dataSave as $attribute=>$value){
                if (!in_array($attribute,$this->savingFields())){
                    unset($dataSave[$attribute]);
                }
            }
        }

        if ($this->notSavingFields()){
            foreach ($this->notSavingFields() as $attribute){
               if (array_key_exists($attribute, $dataSave)){
                   unset($dataSave[$attribute]);
               }
            }
        }


        file_put_contents(static::getDir() . '/' . $this->id,  Json::encode($dataSave));
    }

    protected function savingFields(){
        return;
    }

    protected function notSavingFields(){
        return;
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        $this->trigger(ActiveRecord::EVENT_BEFORE_DELETE);
        return true;
    }

    /***
     *
     */
    protected function afterDelete()
    {
        $this->trigger(ActiveRecord::EVENT_AFTER_DELETE);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if ($this->beforeDelete()) {
            $this->_delete();
            $this->afterDelete();
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function _delete()
    {
        if (is_file(static::getDir() . '/' . $this->id)) {
            unlink(static::getDir() . '/' . $this->id);
        }
    }

    /**
     * @param null $id
     */
    public function setId($id = null)
    {
        if (!$id) {
            $id = uniqid(time());
        }
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    protected static function getDir()
    {
        if (!is_dir(Yii::getAlias('@vendor') . '/../' . static::$dir)){
            FileHelper::createDirectory(Yii::getAlias('@vendor') . '/../' . static::$dir);
        }
        return Yii::getAlias('@vendor') . '/../' . static::$dir;
    }

    /**
     * @param $id
     * @return array|mixed
     * @throws \yii\base\Exception
     */
    protected static function jsonData($id)
    {
        return is_file(static::getDir() . '/' . $id) ? Json::decode(file_get_contents(static::getDir() . '/' . $id), true) : [];
    }

    /**
     *
     */
    public function afterFind()
    {
        $this->trigger(ActiveRecord::EVENT_AFTER_FIND);
    }

    /**
     * @param $id
     * @return null|JsonDataModel
     * @throws \yii\base\Exception
     */
    public static function findById($id)
    {
        $jsonData = static::jsonData($id);
        if ($jsonData) {
            $obj = new static($jsonData);
            $obj->setId($id);
            $obj->_oldAttributes = $jsonData;
            $obj->afterFind();
            return $obj;
        }
        return null;
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    public static function findData()
    {
        $result = [];
        $scanDir = scandir(static::getDir());
        if ($scanDir) {
            foreach ($scanDir as $v) {
                $obj = static::findById($v);
                if ($obj) {
                    $result[$v] = $obj;
                }
            }
        }
        return $result;
    }
}