<?php
/**
 * Created by PhpStorm.
 * User: programmer_5
 * Date: 25.07.2018
 * Time: 13:02
 */

namespace oshchyp\yii2Expansion\helpers;


class VarDumper extends \yii\helpers\VarDumper
{

    public static function dump($var, $kill=false , $depth = 10, $highlight = false)
    {
        echo '<pre>';
        parent::dump($var, $depth, $highlight); // TODO: Change the autogenerated stub
        echo '</pre>';
        if ($kill){
            die();
        }
    }

}