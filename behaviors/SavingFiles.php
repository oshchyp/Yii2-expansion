<?php
/**
 * Created by PhpStorm.
 * User: programmer_5
 * Date: 25.07.2018
 * Time: 16:18
 */

namespace oshchyp\yii2Expansion\behaviors;

use oshchyp\yii2Expansion\helpers\VarDumper;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class SavingFiles extends Behavior
{

    public $basePath = '@app';

    public $baseUrl = '@web';

    public $setFileAttributes = ActiveRecord::EVENT_BEFORE_VALIDATE;

    public $setFileNamesAttributes = ActiveRecord::EVENT_AFTER_VALIDATE;

    public $uploadFiles = ActiveRecord::EVENT_AFTER_UPDATE;

    /**
     * @var
     * example
     * [
     * [[attribute1,attribute2],'dir'=>'test/test','basePath'=>'@webroot', 'fileName' => 'test', 'setFileNameAttribute' => 'test'],
     * ['attribute3','dir'=> function ($model,$attribute){},'basePath'=>'@webroot', 'fileName' => function ($model,$attribute, $fileObject){}, 'setFileNameAttribute' => 'test', 'multiple' => true],
     * ]
     */

    public $rules;

    private $_rulesConvert;

    public function init()
    {
        parent::init();
        if ($this->rules) {
            foreach ($this->rules as $rule){
                $settings = [
                    'dir'=>ArrayHelper::getValue($rule,'dir', ''),
                    'basePath'=>ArrayHelper::getValue($rule,'basePath', ''),
                    'fileName' => ArrayHelper::getValue($rule,'fileName', null),
                    'setFileNameAttribute' => ArrayHelper::getValue($rule,'setFileNameAttribute', null),
                    'multiple' => ArrayHelper::getValue($rule,'multiple',false),
                ];
               if (is_string($rule[0])){
                   $this->_rulesConvert[$rule[0]] = $settings;
               } else if (is_array($rule[0])){
                   foreach ($rule[0] as $attr){
                       $this->_rulesConvert[$attr] = $settings;
                   }
               }
            }
        }
    }

    public function events()
    {
        return [
            $this->setFileAttributes => 'setFileAttributes',
            $this->setFileNamesAttributes => 'setFileNamesAttributes',
            $this->uploadFiles => 'uploadFiles',
        ];
    }

    public function setFileAttributes($event)
    {
        if ($this->_rulesConvert) {
            foreach ($this->_rulesConvert as $k => $v) {
                $this->owner->$k = $v['multiple'] ? UploadedFile::getInstances($this->owner, $k) : UploadedFile::getInstance($this->owner, $k);
            }
        }
    }

    public function setFileNamesAttributes()
    {
        foreach ($this->_rulesConvert as $k => $v) {
            if ($v['setFileNameAttribute'] && $this->owner->$k) {
                $attr = $v['setFileNameAttribute'];
                $this->owner->$attr = $this->_getDir($k).'/'.$this->_getFileName($k, $this->owner->$k);
            }
        }
    }

    public function uploadFiles($event)
    {

        foreach ($this->_rulesConvert as $k => $v) {
            if ($this->owner->$k) {
                if ($v['multiple']) {
                    foreach ($this->owner->$k as $ident => $fileInfo){
                        $filePath = Yii::getAlias($v['basePath']).'/'.Yii::getAlias($this->_getDir($k)) . '/' .$this->_getFileNameMiltiple( $k, $fileInfo, $ident);
                        $fileInfo->saveAs($filePath);
                    }
                } else {
                    $filePath = Yii::getAlias($v['basePath']).'/'.Yii::getAlias($this->_getDir($k)) . '/' . $this->_getFileName($k, $this->owner->$k);
                    $this->owner->$k->saveAs($filePath);
                }
            }
        }

    }

    private function _getDir($attribute){
       $dir = Yii::getAlias($this->_getReal($attribute,'dir', 'dirReal'));
       $basePath = Yii::getAlias($this->_rulesConvert[$attribute]['basePath']);
       if (!is_dir($basePath.'/'.$dir)){
           FileHelper::createDirectory($basePath.'/'.$dir);
       }
       return $dir;
    }

    private function _getFileName($attribute,$fileObject=null){
        return $this->_getReal($attribute,'fileName', 'fileNameReal',$fileObject);
    }

    private function _getFileNameMiltiple($attribute,$fileObject=null,$ident=0){
        $this->_rulesConvert[$ident.'-'.$attribute] = $this->_rulesConvert[$attribute];
        return $this->_getReal($ident.'-'.$attribute,'fileName', 'fileNameReal',$fileObject);
    }

    private function _getReal($attribute,$key, $keyReal, $fileObject = null ){
        if (!isset($this->_rulesConvert[$attribute]))
            return;
        if (!array_key_exists($keyReal,$this->_rulesConvert[$attribute])){
            if (is_callable($this->_rulesConvert[$attribute][$key])){
                $this->_rulesConvert[$attribute][$keyReal] = $this->_rulesConvert[$attribute][$key]($this->owner,$attribute,$fileObject);
            } else if (is_string($this->_rulesConvert[$attribute][$key])){
                $this->_rulesConvert[$attribute][$keyReal] = $this->_rulesConvert[$attribute][$key];
            } else {
                $this->_rulesConvert[$attribute][$keyReal] = '';
            }
        }
        return $this->_rulesConvert[$attribute][$keyReal];
    }


}