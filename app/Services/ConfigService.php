<?php
namespace App\Services;

use App\Libs\RedisKey;
use App\Models\ConfigModel;
use Illuminate\Support\Facades\Redis;

class ConfigService{

    //账户类
    const USER_SEND_CODE_FREQ   = 'user_send_code_frequence';  //用户每分钟发送验证码次数
    const IP_SEND_CODE_FREQ    = 'ip_send_code_frequence';     //IP每分钟发送验证码次数
    const SEND_CODE_LOCK_TIME   = 'send_code_lock_time';        //发送验证码冻结时间
    const VERIFY_CODE_LIMIT     = 'verify_code_limit';          //验证码有效时长
    const SEND_CODE_INTERVAL    = 'send_code_interval';         //两次验证码发送间隔
    const USER_LOGIN_FREQ       = 'user_login_frequency';       //用户每分钟登陆请求频率
    const USER_LOGIN_LOCK_TIME  = 'user_login_lock_time';       //用户登陆冻结时间
    const IP_REGISTER_LIMIT     = 'ip_register_limit';          //IP注册用户数限制
    const USER_WRONG_PWD_INTERVAL   = 'user_wrong_pwd_interval';//用户密码错误统计时长
    const USER_WRONG_PWD_LIMIT      = 'user_wrong_pwd_limit';   //用户冻结登陆密码输入错误次数
    const USER_LOGIN_TOKEN_TIME     = 'user_login_token_time';  //用户登陆Token超时时间
    const USER_RESET_TOKEN_TIME     = 'user_reset_token_time';  //找回密码授权码超时时间
    const USER_ADVICE_FREQUENCY     = 'user_advice_frequency';  //用户投诉建议每分钟次数限制
    const PAGE_SIZE                 = 'page_size';              //分页每页显示数量
    const USER_COMMENT_FREQ         = 'user_comment_frequency';     //用户评论评率限制(分钟)
    const NICK_MODIFY_FREQ          = 'nick_modify_frequency';      //昵称修改评率(天)
    const RECOMMEND_USER_LIMIT      = 'recommend_user_limit';       //每次推荐用户数
    const RECOMMEND_MOMENT_LIMIT    = 'recommend_moment_limit';     //每页推荐精彩动态数
    const RECOMMEND_FANS_RATE       = 'recommend_fans_rate';        //用户粉丝数加权值
    const RECOMMEND_MOMENT_RATE     = 'recommend_moment_rate';      //用户动态数加权值
    const CONDITION_SHARE           = 'condition_share';            //精彩动态条件转发量
    const CONDITION_COMMENT         = 'condition_comment';          //精彩动态条件评论量
    const CONDITION_LIKE            = 'condition_like';             //精彩动态条件点赞量
    const RED_PACK_MAX_NUM          = 'red_pack_max_num';           //红包最大数量限制
    const USER_BIND_ADDRESS         = 'user_bind_address';          //用户绑定提币地址




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





}