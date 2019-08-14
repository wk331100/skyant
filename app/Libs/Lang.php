<?php

namespace App\Libs;

class Lang{
    const CN = 'cn';
    const EN = 'en';
    const TW = 'tw';
    const KR = 'kr';

    public static $default = self::CN;


    public static function langList(){
        return [
            self::CN,
            self::EN,
            self::TW,
            self::KR
        ];
    }

    public static function checkLangExist($lang){
        $langList = self::langList();
        if(in_array($lang, $langList)){
            return true;
        }
        return false;
    }

    public static function validateLang($lang){
        if(self::checkLangExist($lang)){
            return self::$default = $lang;
        }
        return self::$default;
    }




}