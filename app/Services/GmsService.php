<?php
namespace App\Services;

use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Models\SmsPrefixModel;
use Illuminate\Support\Facades\Redis;

class GmsService{

    //短信类
    const SMS_TEMPLATE_VERIFY = 'verify';




    public static function getSmsTemplate($sign, $lang){
        $redisKey = $sign . '_' . $lang;
    }


    /**
     * 获取配置项
     * @param $configKey
     * @return bool|mixed
     */
    public static function getConfig($configKey){
        $redisConfig = Redis::hget(RedisKey::CONFIG_KEY, $configKey);
        if(!empty($redisConfig)){
            return json_decode($redisConfig);
        }

        $config = ConfigModel::getInstance()->getValueByKey($configKey);
        if($config && isset($config->value)){
            Redis::hset(RedisKey::CONFIG_KEY, $configKey, $config->value);
            return $config->value;
        }

        return false;
    }

    /**
     * 获取手机号地区国家列表
     * @return \Illuminate\Support\Collection
     */
    public static function getPrefixList(){
        $list = SmsPrefixModel::getInstance()->getList(true);
        if(empty($list)){
            throw new ServiceException(MessageCode::PREFIX_LIST_EMPTY);
        }
        return $list;
    }


}